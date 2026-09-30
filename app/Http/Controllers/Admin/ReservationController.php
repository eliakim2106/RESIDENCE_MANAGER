<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReservationCancelRequest;
use App\Http\Requests\Admin\ReservationPaymentRequest;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\ReservationWorkflow;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Réservations : toutes pour un administrateur, celles de ses établissements pour un propriétaire,
 * les siennes pour un client. Les actions possibles dépendent du rôle (ReservationPolicy) et du statut (ReservationWorkflow).
 */
class ReservationController extends Controller
{
    /**
     * Onglets de la liste : clé d'URL => [libellé, statuts]
     *
     * @var array<string, array{0: string, 1: list<ReservationStatus>}>
     */
    private const TABS = [
        'toutes' => ['Toutes', []],
        'en-attente' => ['En attente', [ReservationStatus::Pending]],
        'confirmees' => ['Confirmées', [ReservationStatus::Confirmed]],
        'terminees' => ['Terminées', [ReservationStatus::Completed]],
        'annulees' => ['Annulées', [ReservationStatus::Cancelled, ReservationStatus::NoShow]],
    ];

    public function __construct(private ReservationWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search'));
        $tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'toutes';

        $properties = $user->isAdmin() || $user->isOwner()
            ? Property::query()->unless($user->isAdmin(), fn (Builder $query) => $query->ownedBy($user))->orderBy('name')->get(['id', 'name', 'slug'])
            : collect();
        $propertySlug = (string) $request->query('etablissement');
        $property = $properties->firstWhere('slug', $propertySlug);

        $query = $this->scoped($request)
            ->when($property, fn (Builder $query) => $query->where('property_id', $property->id))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('reference', 'like', $like)
                    ->orWhere('guest_name', 'like', $like)
                    ->orWhere('guest_email', 'like', $like)
                    ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', $like))
                    ->orWhereHas('property', fn (Builder $query) => $query->where('name', 'like', $like)));
            });

        $byStatus = (clone $query)->selectRaw('statut, COUNT(*) as total')->groupBy('statut')->pluck('total', 'statut');
        $counts = collect(self::TABS)->map(fn (array $tab): int => $tab[1] === []
            ? (int) $byStatus->sum()
            : (int) collect($tab[1])->sum(fn (ReservationStatus $status) => $byStatus[$status->value] ?? 0));

        $reservations = $query
            ->when(self::TABS[$tab][1] !== [], fn (Builder $query) => $query->whereIn('statut', self::TABS[$tab][1]))
            ->with(['property', 'user'])
            ->orderByDesc('check_in')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'tabs' => array_map(fn (array $tab): string => $tab[0], self::TABS),
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
            'properties' => $properties,
            'propertySlug' => $property?->slug,
        ]);
    }

    public function show(Reservation $reservation): View
    {
        Gate::authorize('view', $reservation);

        $reservation->load(['property.owner', 'property.city', 'items.unit.unitType', 'guests', 'payments' => fn ($query) => $query->latest('id'), 'review', 'user']);

        return view('admin.reservations.show', [
            'reservation' => $reservation,
            'actions' => $this->workflow->availableActions($reservation),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS
    |--------------------------------------------------------------------------
    */

    public function confirm(Reservation $reservation): RedirectResponse
    {
        Gate::authorize('manage', $reservation);

        return $this->run(fn () => $this->workflow->confirm($reservation), 'Réservation validée.');
    }

    public function cancel(ReservationCancelRequest $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('cancel', $reservation);

        // Une demande en attente écartée par l'établissement ou un administrateur est un refus
        $refused = $reservation->statut === ReservationStatus::Pending && $request->user()->can('manage', $reservation);

        return $this->run(fn () => $this->workflow->cancel($reservation, $request->reason()), $refused ? 'Réservation refusée.' : 'Réservation annulée.');
    }

    public function complete(Reservation $reservation): RedirectResponse
    {
        Gate::authorize('manage', $reservation);

        return $this->run(fn () => $this->workflow->complete($reservation), 'Séjour marqué comme terminé.');
    }

    public function noShow(Reservation $reservation): RedirectResponse
    {
        Gate::authorize('manage', $reservation);

        return $this->run(fn () => $this->workflow->markNoShow($reservation), 'Client déclaré non présenté.');
    }

    public function storePayment(ReservationPaymentRequest $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('manage', $reservation);

        return $this->run(
            fn () => $this->workflow->recordPayment($reservation, $request->amount(), $request->method(), $request->reference(), $request->user()),
            'Paiement enregistré.',
        );
    }

    public function updateNotes(Request $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('manage', $reservation);

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);

        $this->workflow->updateNotes($reservation, $validated['notes'] ?? null);

        return back()->with('success', 'Notes internes enregistrées.');
    }

    /*
    |--------------------------------------------------------------------------
    | OUTILS
    |--------------------------------------------------------------------------
    */

    /**
     * Réservations visibles par l'utilisateur connecté.
     *
     * @return Builder<Reservation>
     */
    private function scoped(Request $request): Builder
    {
        $user = $request->user();

        return Reservation::query()
            ->when($user->isOwner(), fn (Builder $query) => $query->whereHas('property', fn (Builder $query) => $query->ownedBy($user)))
            ->when(! $user->isAdmin() && ! $user->isOwner(), fn (Builder $query) => $query->whereBelongsTo($user));
    }

    private function run(Closure $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }
}
