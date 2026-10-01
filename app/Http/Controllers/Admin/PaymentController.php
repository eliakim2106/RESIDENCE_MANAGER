<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentListing;
use App\Services\ReservationWorkflow;
use App\Support\ExcelExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Paiements : tous pour un administrateur, ceux de ses établissements pour un propriétaire.
 * Le client accède seulement au reçu de ses propres paiements.
 */
class PaymentController extends Controller
{
    public function __construct(private ReservationWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $listing = new PaymentListing($request->user(), $request);

        return view('admin.paiements.index', [
            'listing' => $listing,
            'payments' => $listing->paginate(),
            'tabs' => PaymentListing::TABS,
            'counts' => $listing->counts(),
            'summary' => $listing->summary(),
            'timeline' => $listing->timeline(),
            'byMethod' => $listing->byMethod(),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function show(Payment $paiement): View
    {
        Gate::authorize('manage', $paiement);

        $paiement->load(['reservation.property.owner', 'reservation.user', 'user']);

        return view('admin.paiements.show', [
            'payment' => $paiement,
            'reservation' => $paiement->reservation,
            'siblings' => $paiement->reservation?->payments()->whereKeyNot($paiement->id)->latest('id')->get() ?? collect(),
        ]);
    }

    /**
     * Reçu imprimable (et enregistrable en PDF) : aussi accessible au client pour ses paiements.
     */
    public function receipt(Payment $paiement): View
    {
        Gate::authorize('view', $paiement);

        $paiement->load(['reservation.property.city', 'reservation.items.unit']);

        return view('admin.paiements.receipt', ['payment' => $paiement, 'reservation' => $paiement->reservation]);
    }

    /**
     * Rembourse tout ou partie du paiement.
     */
    public function refund(Request $request, Payment $paiement): RedirectResponse
    {
        Gate::authorize('manage', $paiement);

        $validated = $request->validate([
            'montant' => ['required'],
            'motif' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'montant.required' => 'Indiquez le montant à rembourser.',
            'motif.required' => 'Indiquez le motif du remboursement : il est transmis au client.',
            'motif.min' => 'Le motif doit être un peu plus détaillé.',
        ]);

        // « 25 000 FCFA » devient 25000
        $amount = (int) preg_replace('/\D/', '', (string) $validated['montant']);

        try {
            $this->workflow->refundPayment($paiement, $amount, trim($validated['motif']));
        } catch (WorkflowException $exception) {
            return back()->withInput()->withErrors(['montant' => $exception->getMessage()]);
        }

        return back()->with('success', 'Remboursement de '.number_format($amount, 0, ',', ' ').' FCFA enregistré. Le client est prévenu par email.');
    }

    /**
     * Export CSV de la liste filtrée (Excel : séparateur « ; », UTF-8).
     */
    public function export(Request $request): BinaryFileResponse
    {
        $listing = new PaymentListing($request->user(), $request);

        return ExcelExport::download('paiements', 'Paiements', [
            ['label' => 'Transaction', 'width' => 26],
            ['label' => 'Date', 'type' => 'datetime'],
            ['label' => 'Statut', 'width' => 13],
            ['label' => 'Moyen', 'width' => 18],
            ['label' => 'Opérateur', 'width' => 12],
            ['label' => 'Référence opérateur', 'width' => 20],
            ['label' => 'Montant', 'type' => 'money'],
            ['label' => 'Remboursé', 'type' => 'money'],
            ['label' => 'Net', 'type' => 'money'],
            ['label' => 'Réservation', 'width' => 16],
            ['label' => 'Client', 'width' => 24],
            ['label' => 'Établissement', 'width' => 26],
            ['label' => 'Source', 'width' => 20],
        ], $listing->query()->with('reservation.property')->lazy(200)->map(fn (Payment $payment): array => [
            $payment->transaction_id,
            $payment->paid_at ?? $payment->created_at,
            $payment->statut,
            $payment->method,
            $payment->operator,
            $payment->operator_reference,
            $payment->amount,
            $payment->refunded_amount,
            $payment->isAccepted() ? $payment->netAmount() : 0,
            $payment->reservation?->reference,
            $payment->reservation?->guest_name,
            $payment->reservation?->property?->name,
            $payment->isManual() ? 'Saisie manuelle' : 'En ligne ('.$payment->provider.')',
        ]));
    }
}
