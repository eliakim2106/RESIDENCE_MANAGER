<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentState;
use App\Enums\ReservationStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\ReservationActionException;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cycle de vie d'une réservation : en attente → confirmée → terminée,
 * avec annulation ou absence (« non présenté ») possibles, et encaissements manuels.
 *
 * Chaque action vérifie l'état de la réservation et lève ReservationActionException
 * avec un message destiné à l'utilisateur quand elle n'est pas permise.
 */
class ReservationWorkflow
{
    /*
    |--------------------------------------------------------------------------
    | CHANGEMENTS DE STATUT
    |--------------------------------------------------------------------------
    */

    public function confirm(Reservation $reservation): void
    {
        $this->expectStatus($reservation, [ReservationStatus::Pending], 'Seule une réservation en attente peut être confirmée.');

        $reservation->update([
            'status' => ReservationStatus::Confirmed,
            'confirmed_at' => now(),
            'expires_at' => null,
        ]);
    }

    public function cancel(Reservation $reservation, ?string $reason): void
    {
        $this->expectStatus($reservation, [ReservationStatus::Pending, ReservationStatus::Confirmed], 'Cette réservation ne peut plus être annulée.');

        $reservation->update([
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Séjour terminé : possible à partir du jour du départ.
     */
    public function complete(Reservation $reservation): void
    {
        $this->expectStatus($reservation, [ReservationStatus::Confirmed], 'Seule une réservation confirmée peut être clôturée.');

        if ($reservation->check_out->isFuture()) {
            throw new ReservationActionException('Le séjour ne peut être clôturé qu’à partir du jour du départ ('.$reservation->check_out->format('d/m/Y').').');
        }

        $reservation->update(['status' => ReservationStatus::Completed]);
    }

    /**
     * Client absent : possible à partir du jour de l'arrivée.
     */
    public function markNoShow(Reservation $reservation): void
    {
        $this->expectStatus($reservation, [ReservationStatus::Confirmed], 'Seule une réservation confirmée peut être déclarée « non présenté ».');

        if ($reservation->check_in->isFuture()) {
            throw new ReservationActionException('Le client ne peut être déclaré absent qu’à partir du jour de l’arrivée ('.$reservation->check_in->format('d/m/Y').').');
        }

        $reservation->update(['status' => ReservationStatus::NoShow]);
    }

    public function updateNotes(Reservation $reservation, ?string $notes): void
    {
        $reservation->update(['owner_notes' => $notes]);
    }

    /*
    |--------------------------------------------------------------------------
    | ENCAISSEMENTS
    |--------------------------------------------------------------------------
    */

    /**
     * Enregistre un paiement reçu hors ligne (espèces, Mobile Money à la réception…) et met à jour le solde.
     */
    public function recordPayment(Reservation $reservation, int $amount, PaymentMethod $method, ?string $reference, User $recordedBy): Payment
    {
        $this->expectStatus($reservation, [ReservationStatus::Pending, ReservationStatus::Confirmed, ReservationStatus::Completed], 'Aucun paiement ne peut être enregistré sur une réservation annulée.');

        $balance = $reservation->balanceDue();

        if ($amount > $balance) {
            throw new ReservationActionException('Le montant dépasse le solde restant dû ('.number_format($balance, 0, ',', ' ').' FCFA).');
        }

        return DB::transaction(function () use ($reservation, $amount, $method, $reference, $recordedBy): Payment {
            $payment = $reservation->payments()->create([
                'user_id' => $recordedBy->id,
                'transaction_id' => 'MAN-'.Str::upper(Str::random(12)),
                'provider' => 'manuel',
                'method' => $method,
                'amount' => $amount,
                'currency' => $reservation->currency ?? 'XOF',
                'status' => TransactionStatus::Accepted,
                'operator_reference' => $reference,
                'paid_at' => now(),
            ]);

            $paid = $reservation->amount_paid + $amount;

            $reservation->update([
                'amount_paid' => $paid,
                'payment_state' => $paid >= $reservation->total_amount ? PaymentState::Paid : PaymentState::Partial,
            ]);

            return $payment;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | OUTILS
    |--------------------------------------------------------------------------
    */

    /**
     * Actions disponibles sur une réservation pour la vue (les droits sont vérifiés à part, par la policy).
     *
     * @return array{confirm: bool, cancel: bool, complete: bool, noShow: bool, payment: bool}
     */
    public function availableActions(Reservation $reservation): array
    {
        $status = $reservation->status;

        return [
            'confirm' => $status === ReservationStatus::Pending,
            'cancel' => in_array($status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true),
            'complete' => $status === ReservationStatus::Confirmed && ! $reservation->check_out->isFuture(),
            'noShow' => $status === ReservationStatus::Confirmed && ! $reservation->check_in->isFuture(),
            'payment' => $status !== ReservationStatus::Cancelled && $status !== ReservationStatus::NoShow && $reservation->balanceDue() > 0,
        ];
    }

    /**
     * @param  list<ReservationStatus>  $allowed
     */
    private function expectStatus(Reservation $reservation, array $allowed, string $message): void
    {
        if (! in_array($reservation->status, $allowed, true)) {
            throw new ReservationActionException($message);
        }
    }
}
