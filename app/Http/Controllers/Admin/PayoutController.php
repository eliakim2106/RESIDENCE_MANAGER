<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayoutMethod;
use App\Enums\PayoutStatus;
use App\Enums\UserRole;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\User;
use App\Services\PayoutLedger;
use App\Support\ExcelExport;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Reversements aux propriétaires (administrateurs) : soldes, enregistrement des virements, historique.
 * Le relevé imprimable d'un reversement est aussi ouvert au propriétaire concerné.
 */
class PayoutController extends Controller
{
    private const TABS = [
        'a-reverser' => 'À reverser',
        'tous' => 'Tous les propriétaires',
        'historique' => 'Historique',
    ];

    public function __construct(private PayoutLedger $ledger) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'a-reverser';
        $like = '%'.addcslashes($search, '%_\\').'%';

        // Soldes de tous les propriétaires (calculés en mémoire : triés du plus gros montant à reverser au plus petit)
        $owners = User::query()
            ->where('role', UserRole::Owner)
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('company_name', 'like', $like)))
            ->with(['currentSubscription.plan', 'payouts' => fn ($query) => $query->where('statut', PayoutStatus::Paid)->latest('paid_at')->limit(1)])
            ->orderBy('name')
            ->get();

        $balances = $this->ledger->balances($owners);
        $owners = $owners
            ->each(fn (User $owner) => $owner->setAttribute('balance', $balances[$owner->id]))
            ->sortByDesc(fn (User $owner) => [$owner->balance['available'], $owner->balance['upcoming']])
            ->values();
        $toPay = $owners->filter(fn (User $owner) => $owner->balance['available'] > 0)->values();

        $payouts = Payout::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('number', 'like', $like)
                ->orWhere('reference', 'like', $like)
                ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like))));

        $counts = [
            'a-reverser' => $toPay->count(),
            'tous' => $owners->count(),
            'historique' => (clone $payouts)->count(),
        ];

        $list = match ($tab) {
            'historique' => $payouts->with(['user', 'recorder'])->latest('paid_at')->latest('id')->paginate(15)->withQueryString(),
            default => $this->paginate($tab === 'tous' ? $owners : $toPay, $request),
        };

        $paid = Payout::query()->where('statut', PayoutStatus::Paid);

        return view('admin.reversements.index', [
            'list' => $list,
            'tabs' => self::TABS,
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
            'summary' => [
                'available' => (int) collect($balances)->sum(fn (array $balance) => max(0, $balance['available'])),
                'upcoming' => (int) collect($balances)->sum('upcoming'),
                'paidThisMonth' => (int) (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                'commissionThisYear' => (int) (clone $paid)->where('paid_at', '>=', now()->startOfYear())->sum('commission_amount'),
            ],
        ]);
    }

    /**
     * Export Excel de l'historique des reversements (recherche en cours).
     */
    public function export(Request $request): BinaryFileResponse
    {
        $search = trim((string) $request->query('search'));
        $like = '%'.addcslashes($search, '%_\\').'%';

        $rows = Payout::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('number', 'like', $like)
                ->orWhere('reference', 'like', $like)
                ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like))))
            ->with(['user', 'recorder'])
            ->latest('paid_at')
            ->latest('id')
            ->lazy(200);

        return ExcelExport::download('reversements', 'Reversements', [
            ['label' => 'Numéro', 'width' => 18],
            ['label' => 'Date du virement', 'type' => 'date', 'width' => 15],
            ['label' => 'Propriétaire', 'width' => 26],
            ['label' => 'Email', 'width' => 28],
            ['label' => 'Moyen', 'width' => 18],
            ['label' => 'Référence', 'width' => 20],
            ['label' => 'Compte crédité', 'width' => 34],
            ['label' => 'Encaissé', 'type' => 'money'],
            ['label' => 'Commission', 'type' => 'money'],
            ['label' => 'Reversé', 'type' => 'money'],
            ['label' => 'Statut', 'width' => 12],
            ['label' => 'Saisi par', 'width' => 22],
        ], $rows->map(fn (Payout $payout): array => [
            $payout->number,
            $payout->paid_at,
            $payout->user?->name,
            $payout->user?->email,
            $payout->method,
            $payout->reference,
            $payout->account,
            $payout->gross_amount,
            $payout->commission_amount,
            $payout->amount,
            $payout->statut,
            $payout->recorder?->name,
        ]));
    }

    /**
     * Solde détaillé d'un propriétaire et enregistrement du reversement.
     */
    public function owner(User $proprietaire): View
    {
        abort_unless($proprietaire->isOwner(), 404);

        $proprietaire->load('currentSubscription.plan');

        return view('admin.reversements.owner', [
            'owner' => $proprietaire,
            'balance' => $this->ledger->balance($proprietaire),
            'rate' => $this->ledger->commissionRate($proprietaire),
            'payouts' => $proprietaire->payouts()->with('recorder')->latest('paid_at')->latest('id')->get(),
            'methods' => PayoutMethod::options(),
        ]);
    }

    public function store(Request $request, User $proprietaire): RedirectResponse
    {
        abort_unless($proprietaire->isOwner(), 404);

        $validated = $request->validate([
            'moyen' => ['required', Rule::enum(PayoutMethod::class)],
            'reference' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payout = $this->ledger->record(
                $proprietaire,
                PayoutMethod::from($validated['moyen']),
                $validated['reference'] ?? null,
                $validated['notes'] ?? null,
                $request->user(),
                isset($validated['date']) ? CarbonImmutable::parse($validated['date'])->setTimeFrom(now()) : null,
            );
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Reversement '.$payout->number.' de '.number_format($payout->amount, 0, ',', ' ').' FCFA enregistré. '.$proprietaire->name.' a été prévenu.');
    }

    public function cancel(Payout $reversement): RedirectResponse
    {
        try {
            $this->ledger->cancel($reversement);
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Reversement '.$reversement->number.' annulé : les montants sont de nouveau à reverser.');
    }

    /**
     * Relevé imprimable (administrateurs et propriétaire concerné).
     */
    public function statement(Request $request, Payout $reversement): View
    {
        abort_unless($request->user()->isAdmin() || $reversement->user_id === $request->user()->id, 403);

        $reversement->load(['user', 'recorder', 'items' => fn ($query) => $query->oldest('id'), 'items.payment.reservation.property']);

        return view('admin.reversements.statement', ['payout' => $reversement]);
    }

    /**
     * @param  Collection<int, User>  $owners
     */
    private function paginate($owners, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator($owners->forPage($page, 15)->values(), $owners->count(), 15, $page, [
            'path' => $request->url(),
        ]))->withQueryString();
    }
}
