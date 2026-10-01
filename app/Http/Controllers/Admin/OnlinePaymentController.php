<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\SubscriptionInvoice;
use App\Services\Payments\OnlinePayments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Lancement d'un paiement en ligne : le client règle sa réservation, le propriétaire sa facture d'abonnement.
 * L'utilisateur est envoyé sur le guichet CinetPay.
 */
class OnlinePaymentController extends Controller
{
    public function __construct(private OnlinePayments $payments) {}

    public function reservation(Request $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('pay', $reservation);

        try {
            return redirect()->away($this->payments->startReservation($reservation, $request->user()));
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function invoice(Request $request, SubscriptionInvoice $facture): RedirectResponse
    {
        abort_unless($facture->user_id === $request->user()->id, 403);

        try {
            return redirect()->away($this->payments->startInvoice($facture, $request->user()));
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }
}
