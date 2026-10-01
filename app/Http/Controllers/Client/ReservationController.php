<?php

namespace App\Http\Controllers\Client;

use App\Enums\ReservationStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\Payments\OnlinePayments;
use App\Services\Payments\PaymentGateways;
use App\Services\ReservationWorkflow;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Réservations du client : suivi, annulation, paiement en ligne, avis après le séjour.
 */
class ReservationController extends Controller
{
    /**
     * Onglets : libellé et condition.
     *
     * @var array<string, string>
     */
    public const TABS = ['a-venir' => 'À venir', 'passees' => 'Passées', 'annulees' => 'Annulées'];

    public function __construct(private ReservationWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('onglet'), self::TABS) ? (string) $request->query('onglet') : 'a-venir';
        $user = $request->user();

        $counts = collect(array_keys(self::TABS))->mapWithKeys(fn (string $key): array => [$key => $this->forTab($user->reservations(), $key)->count()])->all();

        $reservations = $this->forTab($user->reservations(), $tab)
            ->with(['property.city', 'property.coverImage', 'items.unit', 'review'])
            ->when($tab === 'a-venir', fn ($query) => $query->orderBy('check_in'), fn ($query) => $query->latest('check_in'))
            ->paginate(8)
            ->withQueryString();

        return view('client.reservations.index', compact('reservations', 'counts', 'tab'));
    }

    public function show(Request $request, Reservation $reservation): View
    {
        Gate::authorize('view', $reservation);

        $reservation->load(['property.city', 'property.coverImage', 'items.unit.images', 'payments' => fn ($query) => $query->latest('id'), 'review']);

        return view('client.reservations.show', [
            'reservation' => $reservation,
            'canCancel' => $request->user()->can('cancel', $reservation),
            'canPay' => $this->canPay($reservation),
            'canReview' => $this->canReview($reservation),
            'deadline' => $reservation->freeCancellationDeadline(),
        ]);
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('cancel', $reservation);

        $validated = $request->validate(['motif' => ['nullable', 'string', 'max:500']]);
        $deadline = $reservation->freeCancellationDeadline();
        // Annulation gratuite encore possible : ce qui a été payé est remboursé
        $refund = $reservation->amount_paid > 0 && $deadline !== null && $deadline->isFuture();

        try {
            $this->workflow->cancel($reservation, $validated['motif'] ?? 'Annulée par le client', $request->user(), refund: $refund);
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('client.reservations.show', $reservation)->with('success', $refund
            ? 'Réservation annulée. Le remboursement de vos paiements est enregistré : il vous est reversé par le moyen utilisé.'
            : 'Réservation annulée. L’établissement a été prévenu.');
    }

    public function pay(Request $request, Reservation $reservation, OnlinePayments $payments): RedirectResponse
    {
        Gate::authorize('pay', $reservation);

        try {
            return redirect()->away($payments->startReservation($reservation, $request->user()));
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function review(Request $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('view', $reservation);

        if (! $this->canReview($reservation)) {
            return back()->with('error', 'Vous pourrez donner votre avis après votre séjour, une seule fois par réservation.');
        }

        $criteria = ['proprete' => 'cleanliness', 'confort' => 'comfort', 'emplacement' => 'location', 'accueil' => 'staff', 'rapport' => 'value_for_money'];

        $validated = $request->validate([
            'note' => ['required', 'integer', 'between:1,10'],
            ...array_fill_keys(array_keys($criteria), ['nullable', 'integer', 'between:1,10']),
            'titre' => ['nullable', 'string', 'max:120'],
            'commentaire' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'note.required' => 'Donnez une note globale à votre séjour.',
            'commentaire.required' => 'Racontez votre séjour en quelques mots.',
            'commentaire.min' => 'Votre avis doit faire au moins 10 caractères.',
        ]);

        Review::create([
            'reservation_id' => $reservation->id,
            'user_id' => $request->user()->id,
            'property_id' => $reservation->property_id,
            'rating' => (int) $validated['note'],
            ...collect($criteria)->mapWithKeys(fn (string $column, string $field): array => [$column => $validated[$field] ?? null])->all(),
            'title' => $validated['titre'] ?? null,
            'comment' => $validated['commentaire'],
            'statut' => ReviewStatus::Approved,
        ]);

        $reservation->property?->refreshRating();

        return back()->with('success', 'Merci pour votre avis ! Il aide les prochains voyageurs à choisir.');
    }

    /*
    |--------------------------------------------------------------------------
    | OUTILS
    |--------------------------------------------------------------------------
    */

    /**
     * @param  Builder<Reservation>|HasMany<Reservation>  $query
     */
    private function forTab($query, string $tab)
    {
        $today = CarbonImmutable::today()->toDateString();

        return match ($tab) {
            'passees' => $query->where(fn ($query) => $query
                ->whereIn('statut', [ReservationStatus::Completed, ReservationStatus::NoShow])
                ->orWhere(fn ($query) => $query->where('statut', ReservationStatus::Confirmed)->whereDate('check_out', '<', $today))),
            'annulees' => $query->where('statut', ReservationStatus::Cancelled),
            default => $query->whereIn('statut', [ReservationStatus::Pending, ReservationStatus::Confirmed])->whereDate('check_out', '>=', $today),
        };
    }

    private function canPay(Reservation $reservation): bool
    {
        return PaymentGateways::available()
            && in_array($reservation->statut, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)
            && $reservation->balanceDue() > 0;
    }

    /**
     * Avis possible une fois le séjour terminé (ou la date de départ passée), une seule fois.
     */
    private function canReview(Reservation $reservation): bool
    {
        return $reservation->review === null
            && ($reservation->statut === ReservationStatus::Completed
                || ($reservation->statut === ReservationStatus::Confirmed && $reservation->check_out->isPast()));
    }
}
