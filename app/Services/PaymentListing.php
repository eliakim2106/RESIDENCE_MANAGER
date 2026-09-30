<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Liste des paiements : périmètre selon le rôle, onglet, moyen de paiement, établissement, période et tri.
 * Fournit aussi la synthèse et les données des graphiques. Partagée par la page et par l'export.
 */
class PaymentListing
{
    /**
     * Onglets : clé d'URL => libellé
     *
     * @var array<string, string>
     */
    public const TABS = [
        'tous' => 'Tous',
        'encaisses' => 'Encaissés',
        'en-cours' => 'En cours',
        'rembourses' => 'Remboursés',
        'echoues' => 'Refusés / annulés',
    ];

    /**
     * @var array<string, string>
     */
    public const PERIODS = [
        'aujourdhui' => 'Aujourd’hui',
        'semaine' => 'Cette semaine',
        'mois' => 'Ce mois',
    ];

    /**
     * Colonnes triables : clé d'URL => expression SQL
     *
     * @var array<string, string>
     */
    public const SORTS = [
        'date' => 'COALESCE(paid_at, created_at)',
        'montant' => 'amount',
    ];

    /**
     * Date d'un paiement : encaissement, à défaut création
     */
    private const DATE = 'DATE(COALESCE(payments.paid_at, payments.created_at))';

    public readonly string $tab;

    public readonly string $search;

    public readonly ?PaymentMethod $method;

    public readonly ?string $period;

    public readonly ?CarbonImmutable $from;

    public readonly ?CarbonImmutable $to;

    public readonly ?string $sort;

    public readonly string $direction;

    private ?Collection $properties = null;

    private ?Property $property = null;

    public function __construct(private readonly User $user, private readonly Request $request)
    {
        $this->tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'tous';
        $this->search = trim((string) $request->query('search'));
        $this->method = PaymentMethod::tryFrom((string) $request->query('moyen'));

        [$this->period, $this->from, $this->to] = $this->resolvePeriod();

        $this->sort = array_key_exists((string) $request->query('tri'), self::SORTS) ? (string) $request->query('tri') : null;
        $this->direction = $request->query('ordre') === 'asc' ? 'asc' : 'desc';
    }

    /*
    |--------------------------------------------------------------------------
    | ÉTABLISSEMENTS
    |--------------------------------------------------------------------------
    */

    /**
     * @return Collection<int, Property>
     */
    public function properties(): Collection
    {
        return $this->properties ??= Property::query()
            ->unless($this->user->isAdmin(), fn (Builder $query) => $query->ownedBy($this->user))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    public function property(): ?Property
    {
        return $this->property ??= $this->properties()->firstWhere('slug', (string) $this->request->query('etablissement'));
    }

    /*
    |--------------------------------------------------------------------------
    | REQUÊTES
    |--------------------------------------------------------------------------
    */

    /**
     * Filtres communs à tous les onglets.
     *
     * @return Builder<Payment>
     */
    public function filtered(): Builder
    {
        return Payment::query()
            ->unless($this->user->isAdmin(), fn (Builder $query) => $query->whereHas('reservation.property', fn (Builder $query) => $query->ownedBy($this->user)))
            ->when($this->property(), fn (Builder $query, Property $property) => $query->whereHas('reservation', fn (Builder $query) => $query->whereBelongsTo($property)))
            ->when($this->method, fn (Builder $query, PaymentMethod $method) => $query->where('method', $method))
            ->when($this->from, fn (Builder $query) => $query
                ->whereRaw(self::DATE.' >= ?', [$this->from->toDateString()])
                ->whereRaw(self::DATE.' <= ?', [$this->to->toDateString()]))
            ->when($this->search !== '', function (Builder $query): void {
                $like = '%'.addcslashes($this->search, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('transaction_id', 'like', $like)
                    ->orWhere('operator_reference', 'like', $like)
                    ->orWhereHas('reservation', fn (Builder $query) => $query
                        ->where('reference', 'like', $like)
                        ->orWhere('guest_name', 'like', $like)
                        ->orWhere('guest_email', 'like', $like)));
            });
    }

    /**
     * @return Builder<Payment>
     */
    public function query(): Builder
    {
        return $this->applyTab($this->filtered(), $this->tab)
            ->when(
                $this->sort,
                fn (Builder $query) => $query->orderByRaw(self::SORTS[$this->sort].' '.$this->direction),
                fn (Builder $query) => $query->orderByRaw(self::SORTS['date'].' desc'),
            )
            ->orderByDesc('id');
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['reservation.property', 'user'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        return array_map(fn (string $tab): int => $this->applyTab($this->filtered(), $tab)->count(), array_combine(array_keys(self::TABS), array_keys(self::TABS)));
    }

    /**
     * Chiffres de synthèse, avec les filtres appliqués (hors onglet).
     *
     * @return array{collected: int, collectedCount: int, pending: int, pendingCount: int, refunded: int, average: int}
     */
    public function summary(): array
    {
        $accepted = $this->filtered()->whereIn('statut', [TransactionStatus::Accepted, TransactionStatus::Refunded]);

        $collected = (int) (clone $accepted)->where('statut', TransactionStatus::Accepted)->selectRaw('COALESCE(SUM(amount - refunded_amount), 0) as total')->value('total');
        $collectedCount = (clone $accepted)->where('statut', TransactionStatus::Accepted)->count();

        return [
            'collected' => $collected,
            'collectedCount' => $collectedCount,
            'pending' => (int) $this->filtered()->where('statut', TransactionStatus::Pending)->sum('amount'),
            'pendingCount' => $this->filtered()->where('statut', TransactionStatus::Pending)->count(),
            'refunded' => (int) $accepted->sum('refunded_amount'),
            'average' => $collectedCount > 0 ? intdiv($collected, $collectedCount) : 0,
        ];
    }

    /**
     * Encaissements nets dans le temps : par jour sur une période courte, sinon par mois (6 derniers mois par défaut).
     *
     * @return array{unit: string, points: list<array{label: string, long: string, value: int, current: bool}>}
     */
    public function timeline(): array
    {
        $daily = $this->from !== null && $this->from->diffInDays($this->to) <= 62;
        $from = $this->from ?? CarbonImmutable::today()->startOfMonth()->subMonths(5);
        $to = $this->to ?? CarbonImmutable::today();

        $payments = $this->filtered()
            ->where('statut', TransactionStatus::Accepted)
            ->when(! $this->from, fn (Builder $query) => $query->whereRaw(self::DATE.' >= ?', [$from->toDateString()]))
            ->get(['amount', 'refunded_amount', 'paid_at', 'created_at'])
            ->groupBy(fn (Payment $payment) => ($payment->paid_at ?? $payment->created_at)->format($daily ? 'Y-m-d' : 'Y-m'));

        $points = [];
        $cursor = $daily ? $from : $from->startOfMonth();

        while ($cursor->lte($to)) {
            $key = $cursor->format($daily ? 'Y-m-d' : 'Y-m');

            $points[] = [
                'label' => $daily ? $cursor->format('d') : $cursor->translatedFormat('M'),
                'long' => $daily ? $cursor->translatedFormat('l d F Y') : $cursor->translatedFormat('F Y'),
                'value' => (int) ($payments->get($key)?->sum(fn (Payment $payment) => $payment->netAmount()) ?? 0),
                'current' => $daily ? $cursor->isToday() : $cursor->isSameMonth(now()),
            ];

            $cursor = $daily ? $cursor->addDay() : $cursor->addMonth();
        }

        return ['unit' => $daily ? 'jour' : 'mois', 'points' => $points];
    }

    /**
     * Répartition des encaissements par moyen de paiement, du plus utilisé au moins utilisé.
     *
     * @return list<array{method: PaymentMethod, value: int, count: int, share: float}>
     */
    public function byMethod(): array
    {
        $rows = $this->filtered()
            ->where('statut', TransactionStatus::Accepted)
            ->selectRaw('method, COUNT(*) as total_count, COALESCE(SUM(amount - refunded_amount), 0) as total_value')
            ->groupBy('method')
            ->get();

        $sum = max(1, (int) $rows->sum('total_value'));

        return $rows
            ->filter(fn ($row) => $row->method !== null)
            ->map(fn ($row): array => [
                'method' => $row->method,
                'value' => (int) $row->total_value,
                'count' => (int) $row->total_count,
                'share' => round((int) $row->total_value / $sum * 100, 1),
            ])
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | OUTILS POUR LA VUE
    |--------------------------------------------------------------------------
    */

    public function periodLabel(): ?string
    {
        if (! $this->from) {
            return null;
        }

        return $this->from->isSameDay($this->to)
            ? 'Paiements du '.$this->from->translatedFormat('d F Y')
            : 'Paiements du '.$this->from->format('d/m').' au '.$this->to->format('d/m/Y');
    }

    public function sortUrl(string $column): string
    {
        $direction = $this->sort === $column && $this->direction === 'desc' ? 'asc' : 'desc';

        return $this->request->fullUrlWithQuery(['tri' => $column, 'ordre' => $direction, 'page' => null]);
    }

    public function sortIcon(string $column): string
    {
        if ($this->sort !== $column) {
            return 'fa-sort';
        }

        return $this->direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    private function applyTab(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'encaisses' => $query->where('statut', TransactionStatus::Accepted),
            'en-cours' => $query->where('statut', TransactionStatus::Pending),
            'rembourses' => $query->where(fn (Builder $query) => $query->where('statut', TransactionStatus::Refunded)->orWhere('refunded_amount', '>', 0)),
            'echoues' => $query->whereIn('statut', [TransactionStatus::Refused, TransactionStatus::Cancelled]),
            default => $query,
        };
    }

    /**
     * @return array{0: ?string, 1: ?CarbonImmutable, 2: ?CarbonImmutable}
     */
    private function resolvePeriod(): array
    {
        $today = CarbonImmutable::today();

        return match ((string) $this->request->query('periode')) {
            'aujourdhui' => ['aujourdhui', $today, $today],
            'semaine' => ['semaine', $today->startOfWeek(), $today->endOfWeek()->startOfDay()],
            'mois' => ['mois', $today->startOfMonth(), $today->endOfMonth()->startOfDay()],
            default => $this->customPeriod(),
        };
    }

    /**
     * @return array{0: ?string, 1: ?CarbonImmutable, 2: ?CarbonImmutable}
     */
    private function customPeriod(): array
    {
        $from = $this->date('du');
        $to = $this->date('au');

        if (! $from && ! $to) {
            return [null, null, null];
        }

        $from ??= $to;
        $to ??= $from;

        return $from->lte($to) ? ['personnalisee', $from, $to] : ['personnalisee', $to, $from];
    }

    private function date(string $key): ?CarbonImmutable
    {
        $value = (string) $this->request->query($key);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
