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
use App\Notifications\ReservationUpdated;
use App\Services\SubscriptionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Paiement en ligne des réservations (client) et des factures d'abonnement (propriétaire) via CinetPay.
 *
 * start…() crée la transaction puis renvoie l'adresse du guichet CinetPay ; sync() interroge CinetPay
 * et applique le résultat (appelé par la notification, au retour du client et par la commande planifiée).
 * sync() est idempotent : une transaction déjà traitée n'est jamais comptée deux fois.
 */
class OnlinePayments
{
    public const ACCEPTED = 'accepted';

    public const REFUSED = 'refused';

    public const PENDING = 'pending';

    public function __construct(
        private CinetPay $cinetpay,
        private SubscriptionManager $subscriptions,
    ) {}

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
            'provider' => 'cinetpay',
            'amount' => CinetPay::payableAmount($balance),
            'currency' => 'XOF',
            'statut' => TransactionStatus::Pending,
        ]);

        $checkout = $this->cinetpay->initialize([
            'transaction_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'description' => 'Reservation '.$reservation->reference.' '.$reservation->property?->name,
            'notify_url' => route('paiements.cinetpay.notify'),
            'return_url' => route('paiements.cinetpay.return', ['transaction' => $payment->transaction_id]),
            'customer' => ['name' => $reservation->guest_name ?: $payer->name, 'email' => $reservation->guest_email ?: $payer->email, 'phone' => $reservation->guest_phone ?: $payer->phone],
            'metadata' => 'reservation:'.$reservation->reference,
        ]);

        $payment->update(['payment_url' => $checkout['payment_url'], 'payment_token' => $checkout['payment_token']]);

        return $checkout['payment_url'];
    }

    /**
     * Règlement d'une facture d'abonnement par le propriétaire.
     */
    public function startInvoice(SubscriptionInvoice $invoice, User $payer): string
    {
        if (! $invoice->isUnpaid()) {
            throw new WorkflowException('Cette facture n’est pas à payer.');
        }

        $invoice->update(['transaction_id' => $this->newTransactionId('ABO')]);

        $checkout = $this->cinetpay->initialize([
            'transaction_id' => $invoice->transaction_id,
            'amount' => $invoice->amount,
            'description' => 'Abonnement '.$invoice->plan_name.' facture '.$invoice->number,
            'notify_url' => route('paiements.cinetpay.notify'),
            'return_url' => route('paiements.cinetpay.return', ['transaction' => $invoice->transaction_id]),
            'customer' => ['name' => $payer->name, 'email' => $payer->email, 'phone' => $payer->phone],
            'metadata' => 'facture:'.$invoice->number,
        ]);

        return $checkout['payment_url'];
    }

    /*
    |--------------------------------------------------------------------------
    | RÉSULTAT
    |--------------------------------------------------------------------------
    */

    /**
     * Interroge CinetPay et applique le statut de la transaction.
     *
     * @return array{status: string, target: Reservation|SubscriptionInvoice}|null null si la transaction est inconnue
     */
    public function sync(string $transactionId): ?array
    {
        if ($payment = Payment::query()->where('transaction_id', $transactionId)->where('provider', 'cinetpay')->first()) {
            return ['status' => $this->syncPayment($payment), 'target' => $payment->reservation];
        }

        if ($invoice = SubscriptionInvoice::query()->where('transaction_id', $transactionId)->first()) {
            return ['status' => $this->syncInvoice($invoice), 'target' => $invoice];
        }

        return null;
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
            ->where('provider', 'cinetpay')
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

        $result = $this->cinetpay->check($payment->transaction_id);
        $status = $this->outcome($result, $payment->amount, $payment->transaction_id);

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

        $result = $this->cinetpay->check($invoice->transaction_id);
        $status = $this->outcome($result, $invoice->amount, $invoice->transaction_id);

        if ($status === self::ACCEPTED) {
            $applied = DB::transaction(function () use ($invoice, $result): bool {
                $invoice = SubscriptionInvoice::query()->lockForUpdate()->find($invoice->id);

                if (! $invoice->isUnpaid()) {
                    return false;
                }

                $this->subscriptions->markPaid($invoice, $this->method($result['method']), $result['operator_id'] ?? $invoice->transaction_id, null);

                return true;
            });

            Log::info('CinetPay : facture d’abonnement réglée en ligne', ['invoice' => $invoice->number, 'applied' => $applied]);
        }

        return $status;
    }

    /**
     * Statut CinetPay → accepté, refusé ou en attente. Un montant inférieur à celui attendu n'est jamais accepté.
     *
     * @param  array{status: string, amount: ?int}  $result
     */
    private function outcome(array $result, int $expected, string $transactionId): string
    {
        if ($result['status'] === CinetPay::ACCEPTED) {
            if ($result['amount'] !== null && $result['amount'] < CinetPay::payableAmount($expected)) {
                Log::warning('CinetPay : montant payé inférieur au montant attendu', ['transaction' => $transactionId, 'paid' => $result['amount'], 'expected' => $expected]);

                return self::PENDING;
            }

            return self::ACCEPTED;
        }

        return in_array($result['status'], [CinetPay::REFUSED, 'CANCELED', 'CANCELLED', 'FAILED'], true) ? self::REFUSED : self::PENDING;
    }

    /**
     * Moyen de paiement CinetPay (OM, MOMO, FLOOZ, WAVE, VISAM, CARD…) → moyen de la plateforme.
     */
    private function method(?string $cinetpayMethod): PaymentMethod
    {
        $code = strtoupper((string) $cinetpayMethod);

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
     * Identifiant unique transmis à CinetPay, ex. « RES-20261001-7KQ2M9XA ».
     */
    private function newTransactionId(string $prefix): string
    {
        return $prefix.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
