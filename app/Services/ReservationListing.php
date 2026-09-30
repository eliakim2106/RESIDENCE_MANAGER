<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Liste des réservations : périmètre selon le rôle, onglet de statut, recherche, établissement,
 * période d'arrivée et tri. Partagée par la page et par l'export, qui affichent donc les mêmes lignes.
 */
class ReservationListing
{
    /**
     * Onglets : clé d'URL => [libellé, statuts]
     *
     * @var array<string, array{0: string, 1: list<ReservationStatus>}>
     */
    public const TABS = [
        'toutes' => ['Toutes', []],
        'en-attente' => ['En attente', [ReservationStatus::Pending]],
        'confirmees' => ['Confirmées', [ReservationStatus::Confirmed]],
        'terminees' => ['Terminées', [ReservationStatus::Completed]],
        'annulees' => ['Annulées', [ReservationStatus::Cancelled, ReservationStatus::NoShow]],
    ];

    /**
     * Périodes d'arrivée proposées en raccourci.
     *
     * @var array<string, string>
     */
    public const PERIODS = [
        'aujourdhui' => 'Aujourd’hui',
        'semaine' => 'Cette semaine',
        'mois' => 'Ce mois',
    ];

    /**
     * Colonnes triables : clé d'URL => colonne
     *
     * @var array<string, string>
     */
    public const SORTS = [
        'reservation' => 'id',
        'sejour' => 'check_in',
        'montant' => 'total_amount',
    ];

    public readonly string $tab;

    public readonly string $search;

    public readonly ?string $period;

    public readonly ?CarbonImmutable $from;

    public readonly ?CarbonImmutable $to;

    public readonly ?string $sort;

    public readonly string $direction;

    private ?Collection $properties = null;

    private ?Property $property = null;

    public function __construct(private readonly User $user, private readonly Request $request)
    {
        $this->tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'toutes';
        $this->search = trim((string) $request->query('search'));

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
     * Établissements proposés dans le filtre (aucun pour un client).
     *
     * @return Collection<int, Property>
     */
    public function properties(): Collection
    {
        return $this->properties ??= $this->user->isAdmin() || $this->user->isOwner()
            ? Property::query()
                ->unless($this->user->isAdmin(), fn (Builder $query) => $query->ownedBy($this->user))
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
            : collect();
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
     * Réservations visibles par l'utilisateur, sans aucun filtre.
     *
     * @return Builder<Reservation>
     */
    public function scoped(): Builder
    {
        return Reservation::query()
            ->when($this->user->isOwner(), fn (Builder $query) => $query->whereHas('property', fn (Builder $query) => $query->ownedBy($this->user)))
            ->when(! $this->user->isAdmin() && ! $this->user->isOwner(), fn (Builder $query) => $query->whereBelongsTo($this->user));
    }

    /**
     * Filtres communs à tous les onglets (établissement, recherche, période).
     *
     * @return Builder<Reservation>
     */
    public function filtered(): Builder
    {
        return $this->scoped()
            ->when($this->property(), fn (Builder $query, Property $property) => $query->whereBelongsTo($property))
            ->when($this->from, fn (Builder $query) => $query
                ->whereDate('check_in', '>=', $this->from->toDateString())
                ->whereDate('check_in', '<=', $this->to->toDateString()))
            ->when($this->search !== '', function (Builder $query): void {
                $like = '%'.addcslashes($this->search, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('reference', 'like', $like)
                    ->orWhere('guest_name', 'like', $like)
                    ->orWhere('guest_email', 'like', $like)
                    ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', $like))
                    ->orWhereHas('property', fn (Builder $query) => $query->where('name', 'like', $like)));
            });
    }

    /**
     * Lignes de l'onglet courant, triées.
     *
     * @return Builder<Reservation>
     */
    public function query(): Builder
    {
        $statuses = self::TABS[$this->tab][1];

        return $this->filtered()
            ->when($statuses !== [], fn (Builder $query) => $query->whereIn('statut', $statuses))
            ->when(
                $this->sort,
                fn (Builder $query) => $query->orderBy(self::SORTS[$this->sort], $this->direction),
                fn (Builder $query) => $query->orderByDesc('check_in'),
            )
            ->orderByDesc('id');
    }

    public function paginate(int $perPage = 12): LengthAwarePaginator
    {
        return $this->query()
            ->with(['property', 'user', 'items.unit'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Nombre de réservations par onglet, avec les autres filtres appliqués.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        $byStatus = $this->filtered()->selectRaw('statut, COUNT(*) as total')->groupBy('statut')->pluck('total', 'statut');

        return array_map(fn (array $tab): int => $tab[1] === []
            ? (int) $byStatus->sum()
            : (int) collect($tab[1])->sum(fn (ReservationStatus $status) => $byStatus[$status->value] ?? 0), self::TABS);
    }

    /**
     * Chiffres du jour, indépendants des filtres.
     *
     * @return array{arrivals: int, departures: int, inHouse: int, pending: int}
     */
    public function today(): array
    {
        $today = today()->toDateString();
        $active = [ReservationStatus::Confirmed, ReservationStatus::Pending];

        return [
            'arrivals' => $this->scoped()->whereIn('statut', $active)->whereDate('check_in', $today)->count(),
            'departures' => $this->scoped()->where('statut', ReservationStatus::Confirmed)->whereDate('check_out', $today)->count(),
            'inHouse' => $this->scoped()->where('statut', ReservationStatus::Confirmed)->whereDate('check_in', '<=', $today)->whereDate('check_out', '>', $today)->count(),
            'pending' => $this->scoped()->where('statut', ReservationStatus::Pending)->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | OUTILS POUR LA VUE
    |--------------------------------------------------------------------------
    */

    /**
     * Libellé de la période active, ex. « Arrivées du 01/10 au 07/10 ».
     */
    public function periodLabel(): ?string
    {
        if (! $this->from) {
            return null;
        }

        return $this->from->isSameDay($this->to)
            ? 'Arrivées du '.$this->from->translatedFormat('d F Y')
            : 'Arrivées du '.$this->from->format('d/m').' au '.$this->to->format('d/m/Y');
    }

    /**
     * Adresse de tri d'une colonne : un clic trie, un second clic inverse l'ordre.
     */
    public function sortUrl(string $column): string
    {
        $direction = $this->sort === $column && $this->direction === 'desc' ? 'asc' : 'desc';

        return $this->request->fullUrlWithQuery(['tri' => $column, 'ordre' => $direction, 'page' => null]);
    }

    /**
     * Icône de tri d'une colonne.
     */
    public function sortIcon(string $column): string
    {
        if ($this->sort !== $column) {
            return 'fa-sort';
        }

        return $this->direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
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
     * Période libre : du … au … (une seule date suffit).
     *
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
