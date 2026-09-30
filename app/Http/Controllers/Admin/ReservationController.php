<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReservationCancelRequest;
use App\Http\Requests\Admin\ReservationPaymentRequest;
use App\Models\Reservation;
use App\Services\ReservationListing;
use App\Services\ReservationWorkflow;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Réservations : toutes pour un administrateur, celles de ses établissements pour un propriétaire,
 * les siennes pour un client. Les actions possibles dépendent du rôle (ReservationPolicy) et du statut (ReservationWorkflow).
 */
class ReservationController extends Controller
{
    public function __construct(private ReservationWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $listing = new ReservationListing($request->user(), $request);

        return view('admin.reservations.index', [
            'listing' => $listing,
            'reservations' => $listing->paginate(),
            'tabs' => array_map(fn (array $tab): string => $tab[0], ReservationListing::TABS),
            'counts' => $listing->counts(),
            'today' => $listing->today(),
        ]);
    }

    /**
     * Export CSV de la liste, avec les filtres affichés (Excel l'ouvre directement : séparateur « ; », encodage UTF-8).
     */
    public function export(Request $request): StreamedResponse
    {
        $listing = new ReservationListing($request->user(), $request);
        $filename = 'reservations-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($listing): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Référence', 'Réservée le', 'Statut', 'Client', 'Email', 'Téléphone', 'Établissement', 'Unités',
                'Arrivée', 'Départ', 'Nuits', 'Adultes', 'Enfants', 'Total (FCFA)', 'Réglé (FCFA)', 'Reste dû (FCFA)', 'Paiement',
            ], ';');

            $listing->query()->with(['property', 'items.unit'])->chunk(200, function ($reservations) use ($out): void {
                foreach ($reservations as $reservation) {
                    fputcsv($out, [
                        $reservation->reference,
                        $reservation->created_at->format('d/m/Y H:i'),
                        $reservation->statut->label(),
                        $reservation->guest_name,
                        $reservation->guest_email,
                        $reservation->guest_phone,
                        $reservation->property?->name,
                        $reservation->items->map(fn ($item) => ($item->quantity > 1 ? $item->quantity.' x ' : '').$item->unit?->name)->filter()->implode(', '),
                        $reservation->check_in->format('d/m/Y'),
                        $reservation->check_out->format('d/m/Y'),
                        $reservation->nights,
                        $reservation->adults,
                        $reservation->children,
                        $reservation->total_amount,
                        $reservation->amount_paid,
                        $reservation->balanceDue(),
                        $reservation->payment_state->label(),
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Bon de réservation imprimable (et enregistrable en PDF depuis la fenêtre d'impression).
     */
    public function voucher(Reservation $reservation): View
    {
        Gate::authorize('view', $reservation);

        $reservation->load(['property.city', 'items.unit.unitType', 'guests', 'user']);

        return view('admin.reservations.voucher', ['reservation' => $reservation]);
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

        return $this->run(fn () => $this->workflow->confirm($reservation), 'Réservation validée. Le client est prévenu par email.');
    }

    public function cancel(ReservationCancelRequest $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('cancel', $reservation);

        // Une demande en attente écartée par l'établissement ou un administrateur est un refus
        $refused = $reservation->statut === ReservationStatus::Pending && $request->user()->can('manage', $reservation);

        // Seuls l'établissement et les administrateurs décident d'un remboursement
        $refund = $request->boolean('rembourser') && $request->user()->can('manage', $reservation);

        return $this->run(
            fn () => $this->workflow->cancel($reservation, $request->reason(), $request->user(), $refund),
            ($refused ? 'Réservation refusée.' : 'Réservation annulée.').($refund ? ' Le remboursement est enregistré.' : ''),
        );
    }

    public function refund(Reservation $reservation): RedirectResponse
    {
        Gate::authorize('manage', $reservation);

        return $this->run(fn () => $this->workflow->refund($reservation), 'Remboursement enregistré. Le client est prévenu par email.');
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
