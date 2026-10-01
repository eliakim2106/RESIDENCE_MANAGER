<?php

namespace App\Http\Controllers;

use App\Exceptions\WorkflowException;
use App\Models\Payment;
use App\Models\SubscriptionInvoice;
use App\Services\Payments\OnlinePayments;
use App\Services\Payments\PaymentGateways;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Adresses appelées par l'agrégateur de paiement (CinetPay, FedaPay).
 *
 * notify et retour sont hors du groupe « web » (ni session, ni CSRF) : l'agrégateur les appelle depuis son site,
 * sans les cookies du client. Le retour renvoie ensuite le client, par une simple redirection, vers « résultat »
 * qui, lui, retrouve sa session et affiche le résultat là où il a lancé le paiement.
 */
class PaymentGatewayController extends Controller
{
    public function __construct(private OnlinePayments $payments) {}

    /**
     * Notification de paiement (serveur à serveur). Le statut est toujours revérifié auprès de l'agrégateur.
     */
    public function notify(Request $request, string $passerelle): Response
    {
        try {
            $gateway = PaymentGateways::driver($passerelle);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        $notified = $gateway->notified($request);

        // L'agrégateur teste aussi l'adresse par un simple appel sans transaction
        if ($notified['transaction'] || $notified['reference']) {
            try {
                $this->payments->syncNotified($gateway, $notified);
            } catch (WorkflowException $exception) {
                Log::warning('Paiement en ligne : notification non traitée', ['gateway' => $passerelle, 'notified' => $notified, 'error' => $exception->getMessage()]);

                return response('Vérification impossible', 503);
            }
        }

        return response('OK');
    }

    /**
     * Retour du client après le paiement.
     */
    public function return(string $transaction): RedirectResponse
    {
        try {
            $this->payments->sync($transaction);
        } catch (WorkflowException) {
            // La notification ou la commande planifiée prendront le relais
        }

        return redirect()->route('paiements.result', ['transaction' => $transaction]);
    }

    /**
     * Résultat affiché au client, dans son espace.
     */
    public function result(Request $request, string $transaction): RedirectResponse
    {
        if ($payment = Payment::query()->where('transaction_id', $transaction)->whereIn('provider', PaymentGateways::names())->with('reservation')->first()) {
            abort_unless($request->user()->can('view', $payment->reservation), 403);

            [$type, $message] = match (true) {
                $payment->isAccepted() => ['success', 'Paiement de '.$this->money($payment->amount).' reçu. Merci ! Un reçu vous a été envoyé par e-mail.'],
                $payment->statut->value === 'pending' => ['warning', 'Votre paiement est en cours de vérification. Cette page sera à jour dès sa confirmation par l’opérateur.'],
                default => ['error', 'Le paiement n’a pas abouti. Aucun montant n’a été débité ; vous pouvez réessayer.'],
            };

            return redirect()->route($request->user()->isClient() ? 'client.reservations.show' : 'admin.reservations.show', $payment->reservation)->with($type, $message);
        }

        $invoice = SubscriptionInvoice::query()->where('transaction_id', $transaction)->firstOrFail();
        abort_unless($invoice->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        [$type, $message] = $invoice->isUnpaid()
            ? ['warning', 'Le paiement de la facture '.$invoice->number.' n’est pas encore confirmé. Si vous avez été débité, il sera pris en compte automatiquement.']
            : ['success', 'Facture '.$invoice->number.' réglée. Merci !'];

        return redirect()->route('admin.abonnement.show')->with($type, $message);
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
