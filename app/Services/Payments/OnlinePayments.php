<?php

namespace App\Services\Payments;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentState;
use App\Enums\ReservationStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\WorkflowException;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use App\Notifications\AdminSubscriptionAlert;
use App\Notifications\ReservationUpdated;
use App\Services\Payments\Gateways\PaymentGateway;
use App\Services\SubscriptionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Paiement en ligne des réservations (client) et des factures d'abonnement (propriétaire).
 *
 * L'agrégateur est celui de PAYMENT_GATEWAY (CinetPay en production, FedaPay en test). start…() crée
 * la transaction puis renvoie l'adresse du guichet ; sync() interroge l'agrégateur qui a ouvert la
 * transaction et applique le résultat (appelé par la notification, au retour du client et par la
 * commande planifiée). sync() est idempotent : une transaction déjà traitée n'est jamais comptée deux fois.
 */
class OnlinePayments
{
    public const ACCEPTED = PaymentGateway::ACCEPTED;

    public const REFUSED = PaymentGateway::REFUSED;

    public const PENDING = PaymentGateway::PENDING;

    public function __construct(private SubscriptionManager $subscriptions) {}

    /*
    |--------------------------------------------------------------------------
    | OUVERTURE DU PAIEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Règlement du solde d'une réservation par le client.
     */
    public function startReservation(Reservation $reservation, User $payer): string
    {
        $gateway = $this->gateway();

        if (! in_array($reservation->statut, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)) {
            throw new WorkflowException('Cette réservation ne peut plus être réglée en ligne.');
        }

        $balance = $reservation->balanceDue();

        if ($balance <= 0) {
            throw new WorkflowException('Cette réservation est déjà entièrement réglée.');
        }

        $payment = $reservation->payments()->create([
            'user_id' => $payer->id,
            'transaction_id' => $this->newTransactionId('RES'),
            'provider' => $gateway->name(),
            'amount' => $gateway->payableAmount($balance),
            'currency' => 'XOF',
            'statut' => TransactionStatus::Pending,
        ]);

        $checkout = $gateway->initialize([
            'transaction_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'description' => 'Reservation '.$reservation->reference.' '.$reservation->property?->name,
            'notify_url' => route('paiements.notify', $gateway->name()),
            'return_url' => route('paiements.return', ['transaction' => $payment->transaction_id]),
            'customer' => ['name' => $reservation->guest_name ?: $payer->name, 'email' => $reservation->guest_email ?: $payer->email, 'phone' => $reservation->guest_phone ?: $payer->phone],
            'metadata' => 'reservation:'.$reservation->reference,
        ]);

        $payment->update(['payment_url' => $checkout['payment_url'], 'payment_token' => $checkout['reference']]);

        return $checkout['payment_url'];
    }

    /**
     * Règlement d'une facture d'abonnement par le propriétaire.
     */
    public function startInvoice(SubscriptionInvoice $invoice, User $payer): string
    {
        $gateway = $this->gateway();

        if (! $invoice->isUnpaid()) {
            throw new WorkflowException('Cette facture n’est pas à payer.');
        }

        $invoice->update(['transaction_id' => $this->newTransactionId('ABO'), 'gateway' => $gateway->name(), 'gateway_reference' => null]);

        $checkout = $gateway->initialize([
            'transaction_id' => $invoice->transaction_id,
            'amount' => $invoice->amount,
            'description' => 'Abonnement '.$invoice->plan_name.' facture '.$invoice->number,
            'notify_url' => route('paiements.notify', $gateway->name()),
            'return_url' => route('paiements.return', ['transaction' => $invoice->transaction_id]),
            'customer' => ['name' => $payer->name, 'email' => $payer->email, 'phone' => $payer->phone],
            'metadata' => 'facture:'.$invoice->number,
        ]);

        $invoice->update(['gateway_reference' => $checkout['reference']]);

        return $checkout['payment_url'];
    }

    /*
    |--------------------------------------------------------------------------
    | RÉSULTAT
    |--------------------------------------------------------------------------
    */

    /**
     * Interroge l'agrégateur et applique le statut de la transaction.
     *
     * @return array{status: string, target: Reservation|SubscriptionInvoice}|null null si la transaction est inconnue
     */
    public function sync(string $transactionId): ?array
    {
        if ($payment = Payment::query()->where('transaction_id', $transactionId)->whereIn('provider', PaymentGateways::names())->first()) {
            return ['status' => $this->syncPayment($payment), 'target' => $payment->reservation];
        }

        if ($invoice = SubscriptionInvoice::query()->where('transaction_id', $transactionId)->first()) {
            return ['status' => $this->syncInvoice($invoice), 'target' => $invoice];
        }

        return null;
    }

    /**
     * Notification reçue d'un agrégateur : il désigne la transaction par notre identifiant ou par le sien.
     *
     * @param  array{transaction: ?string, reference: ?string}  $notified
     */
    public function syncNotified(PaymentGateway $gateway, array $notified): ?array
    {
        $transactionId = $notified['transaction'];

        if (! $transactionId && $notified['reference']) {
            $transactionId = Payment::query()->where('provider', $gateway->name())->where('payment_token', $notified['reference'])->value('transaction_id')
                ?? SubscriptionInvoice::query()->where('gateway', $gateway->name())->where('gateway_reference', $notified['reference'])->value('transaction_id');
        }

        return $transactionId ? $this->sync($transactionId) : null;
    }

    /**
     * Rattrapage (commande planifiée) : revérifie les transactions restées en attente, par exemple quand
     * la notification n'a pas pu joindre le site. Au-delà de 48 h, une transaction en attente est abandonnée.
     *
     * @return array{accepted: int, refused: int, abandoned: int}
     */
    public function syncPending(): array
    {
        $counts = ['accepted' => 0, 'refused' => 0, 'abandoned' => 0];

        Payment::query()
            ->whereIn('provider', PaymentGateways::names())
            ->where('statut', TransactionStatus::Pending)
            ->where('created_at', '<=', now()->subMinutes(2))
            ->each(function (Payment $payment) use (&$counts): void {
                $status = rescue(fn () => $this->syncPayment($payment), self::PENDING, report: false);

                if ($status === self::PENDING && $payment->created_at->lt(now()->subDays(2))) {
                    $payment->update(['statut' => TransactionStatus::Cancelled]);
                    $status = 'abandoned';
                }

                if ($status !== self::PENDING) {
                    $counts[$status]++;
                }
            });

        SubscriptionInvoice::query()
            ->whereNotNull('transaction_id')
            ->where('statut', InvoiceStatus::Unpaid)
            ->where('updated_at', '>=', now()->subDays(2))
            ->each(function (SubscriptionInvoice $invoice) use (&$counts): void {
                if (rescue(fn () => $this->syncInvoice($invoice), self::PENDING, report: false) === self::ACCEPTED) {
                    $counts['accepted']++;
                }
            });

        return $counts;
    }

    private function syncPayment(Payment $payment): string
    {
        if ($payment->statut !== TransactionStatus::Pending) {
            return $payment->isAccepted() || $payment->statut === TransactionStatus::Refunded ? self::ACCEPTED : self::REFUSED;
        }

        $gateway = PaymentGateways::driver($payment->provider);
        $result = $gateway->check($payment->transaction_id, $payment->payment_token);
        $status = $this->outcome($gateway, $result, $payment->amount, $payment->transaction_id);

        if ($status === self::PENDING) {
            return $status;
        }

        $applied = DB::transaction(function () use ($payment, $result, $status): bool {
            // Verrou : la notification et le retour du client peuvent arriver en même temps
            $payment = Payment::query()->lockForUpdate()->find($payment->id);

            if ($payment->statut !== TransactionStatus::Pending) {
                return false;
            }

            $payment->update([
                'statut' => $status === self::ACCEPTED ? TransactionStatus::Accepted : TransactionStatus::Refused,
                'method' => $this->method($result['method']),
                'operator' => $result['method'],
                'operator_reference' => $result['operator_id'],
                'provider_payload' => $result['payload'],
                'paid_at' => $status === self::ACCEPTED ? now() : null,
            ]);

            if ($status === self::ACCEPTED) {
                $reservation = $payment->reservation()->lockForUpdate()->first();
                $paid = $reservation->amount_paid + $payment->amount;

                $reservation->update([
                    'amount_paid' => $paid,
                    'payment_state' => $paid >= $reservation->total_amount ? PaymentState::Paid : PaymentState::Partial,
                ]);
            }

            return true;
        });

        if ($applied && $status === self::ACCEPTED) {
            $this->notifyPaid($payment->fresh('reservation.property.owner'));
        }

        return $status;
    }

    private function syncInvoice(SubscriptionInvoice $invoice): string
    {
        if (! $invoice->isUnpaid()) {
            return self::ACCEPTED;
        }

        $gateway = PaymentGateways::driver($invoice->gateway ?? 'cinetpay');
        $result = $gateway->check($invoice->transaction_id, $invoice->gateway_reference);
        $status = $this->outcome($gateway, $result, $invoice->amount, $invoice->transaction_id);

        if ($status === self::ACCEPTED) {
            $applied = DB::transaction(function () use ($invoice, $result): bool {
                $invoice = SubscriptionInvoice::query()->lockForUpdate()->find($invoice->id);

                if (! $invoice->isUnpaid()) {
                    return false;
                }

                $this->subscriptions->markPaid($invoice, $this->method($result['method']), $result['operator_id'] ?? $invoice->transaction_id, null);

                return true;
            });

            Log::info('Facture d’abonnement réglée en ligne', ['invoice' => $invoice->number, 'gateway' => $gateway->name(), 'applied' => $applied]);

            if ($applied) {
                $invoice->refresh()->load('subscription.user');
                Notification::send(User::query()->backOffice()->get(), new AdminSubscriptionAlert($invoice->subscription, AdminSubscriptionAlert::INVOICE_PAID_ONLINE, $invoice));
            }
        }

        return $status;
    }

    /**
     * Un montant inférieur à celui attendu n'est jamais accepté.
     *
     * @param  array{status: string, amount: ?int}  $result
     */
    private function outcome(PaymentGateway $gateway, array $result, int $expected, string $transactionId): string
    {
        if ($result['status'] === self::ACCEPTED && $result['amount'] !== null && $result['amount'] < $gateway->payableAmount($expected)) {
            Log::warning('Paiement en ligne : montant payé inférieur au montant attendu', ['gateway' => $gateway->name(), 'transaction' => $transactionId, 'paid' => $result['amount'], 'expected' => $expected]);

            return self::PENDING;
        }

        return $result['status'];
    }

    /**
     * Moyen indiqué par l'agrégateur (OM, MOMO, WAVE, mtn_open, VISAM, card…) → moyen de la plateforme.
     */
    private function method(?string $gatewayMethod): PaymentMethod
    {
        $code = strtoupper((string) $gatewayMethod);

        return match (true) {
            str_contains($code, 'VISA'), str_contains($code, 'MASTER'), str_contains($code, 'CARD') => PaymentMethod::Card,
            default => PaymentMethod::MobileMoney,
        };
    }

    private function notifyPaid(Payment $payment): void
    {
        $reservation = $payment->reservation;
        $receipt = new ReservationUpdated($reservation, ReservationUpdated::PAID, $payment->amount);

        if ($reservation->user) {
            $reservation->user->notify($receipt);
        } elseif (filled($reservation->guest_email)) {
            Notification::route('mail', $reservation->guest_email)->notify($receipt);
        }

        $reservation->property?->owner?->notify(new ReservationUpdated($reservation, ReservationUpdated::PAID_FOR_OWNER, $payment->amount));
    }

    /**
     * Agrégateur pour un nouveau paiement.
     */
    private function gateway(): PaymentGateway
    {
        return PaymentGateways::current() ?? throw new WorkflowException('Le paiement en ligne n’est pas encore activé.');
    }

    /**
     * Identifiant unique transmis à l'agrégateur, ex. « RES-20261001-7KQ2M9XA ».
     */
    private function newTransactionId(string $prefix): string
    {
        return $prefix.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
