<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentState;
use App\Enums\ReservationStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\WorkflowException;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ArrivalsReminder;
use App\Notifications\ReservationUpdated;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Cycle de vie d'une réservation : en attente → confirmée → terminée,
 * avec refus, annulation ou absence (« non présenté ») possibles, encaissements manuels et remboursement.
 * Le client est prévenu par email de chaque décision qui le concerne.
 *
 * Chaque action vérifie l'état de la réservation et lève WorkflowException
 * avec un message destiné à l'utilisateur quand elle n'est pas permise.
 */
class ReservationWorkflow
{
    /*
    |--------------------------------------------------------------------------
    | CHANGEMENTS DE STATUT
    |--------------------------------------------------------------------------
    */

    /**
     * Nouvelle réservation : l'établissement est prévenu (dans son espace et par e-mail).
     * À appeler par le parcours de réservation, une fois la réservation enregistrée.
     */
    public function announce(Reservation $reservation): void
    {
        $reservation->loadMissing('property.owner');

        $reservation->property?->owner?->notify(new ReservationUpdated($reservation, ReservationUpdated::NEW_FOR_OWNER));
    }

    /**
     * Rappel quotidien : chaque propriétaire reçoit la liste des clients qui arrivent le lendemain.
     * Un seul envoi par jour, même si la commande est relancée.
     *
     * @return int nombre de propriétaires prévenus
     */
    public function remindArrivals(?CarbonImmutable $day = null): int
    {
        $day ??= CarbonImmutable::tomorrow();

        if (! Cache::add('reservations:arrivals-reminder:'.$day->toDateString(), true, now()->addDays(2))) {
            return 0;
        }

        return Reservation::query()
            ->where('statut', ReservationStatus::Confirmed)
            ->whereDate('check_in', $day->toDateString())
            ->with('property.owner')
            ->orderBy('estimated_arrival_time')
            ->get()
            ->groupBy(fn (Reservation $reservation) => $reservation->property?->owner_id)
            ->filter(fn ($reservations, $ownerId) => $ownerId && $reservations->first()->property?->owner)
            ->each(fn ($reservations) => $reservations->first()->property->owner->notify(new ArrivalsReminder($reservations->values(), $day)))
            ->count();
    }

    public function confirm(Reservation $reservation): void
    {
        $this->expectStatus($reservation, [ReservationStatus::Pending], 'Seule une réservation en attente peut être confirmée.');

        $reservation->update([
            'statut' => ReservationStatus::Confirmed,
            'confirmed_at' => now(),
            'expires_at' => null,
        ]);

        $this->notifyGuest($reservation, ReservationUpdated::CONFIRMED);
    }

    /**
     * Annulation par le client, ou par l'établissement / un administrateur (un « refus » si la demande était en attente).
     * Les sommes déjà réglées peuvent être remboursées dans la foulée.
     */
    public function cancel(Reservation $reservation, ?string $reason, ?User $by = null, bool $refund = false): void
    {
        $this->expectStatus($reservation, [ReservationStatus::Pending, ReservationStatus::Confirmed], 'Cette réservation ne peut plus être annulée.');

        $byGuest = $by !== null && $reservation->user_id === $by->id;
        $event = match (true) {
            $byGuest => ReservationUpdated::CANCELLED_BY_GUEST,
            $reservation->statut === ReservationStatus::Pending => ReservationUpdated::REFUSED,
            default => ReservationUpdated::CANCELLED,
        };

        $reservation->update([
            'statut' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        if ($byGuest) {
            // Le client sait qu'il a annulé : l'établissement, lui, doit l'apprendre
            $reservation->property?->owner?->notify(new ReservationUpdated($reservation, $event));
        } else {
            $this->notifyGuest($reservation, $event);
        }

        if ($refund && $reservation->amount_paid > 0) {
            $this->refund($reservation);
        }
    }

    /**
     * Demandes restées sans réponse de l'établissement après leur délai : annulées, client et établissement prévenus.
     * Un paiement déjà effectué est remboursé (les dates ne sont plus réservées au client).
     *
     * @return int nombre de demandes expirées
     */
    public function expirePending(): int
    {
        $expired = 0;

        Reservation::query()
            ->where('statut', ReservationStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with(['user', 'property.owner'])
            ->each(function (Reservation $reservation) use (&$expired): void {
                $reservation->update([
                    'statut' => ReservationStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Demande expirée : l’établissement n’a pas répondu dans le délai prévu.',
                ]);

                // Montant payé relevé avant le remboursement (l'email part ensuite, en arrière-plan)
                $this->notifyGuest($reservation, ReservationUpdated::EXPIRED, (int) $reservation->amount_paid);
                $reservation->property?->owner?->notify(new ReservationUpdated($reservation, ReservationUpdated::EXPIRED_FOR_OWNER));

                if ($reservation->amount_paid > 0) {
                    $this->refund($reservation);
                }

                $expired++;
            });

        return $expired;
    }

    /**
     * Séjour terminé : possible à partir du jour du départ.
     */
    public function complete(Reservation $reservation): void
    {
        $this->expectStatus($reservation, [ReservationStatus::Confirmed], 'Seule une réservation confirmée peut être clôturée.');

        if ($reservation->check_out->isFuture()) {
            throw new WorkflowException('Le séjour ne peut être clôturé qu’à partir du jour du départ ('.$reservation->check_out->format('d/m/Y').').');
        }

        $reservation->update(['statut' => ReservationStatus::Completed]);
    }

    /**
     * Client absent : possible à partir du jour de l'arrivée.
     */
    public function markNoShow(Reservation $reservation): void
    {
        $this->expectStatus($reservation, [ReservationStatus::Confirmed], 'Seule une réservation confirmée peut être déclarée « non présenté ».');

        if ($reservation->check_in->isFuture()) {
            throw new WorkflowException('Le client ne peut être déclaré absent qu’à partir du jour de l’arrivée ('.$reservation->check_in->format('d/m/Y').').');
        }

        $reservation->update(['statut' => ReservationStatus::NoShow]);
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
            throw new WorkflowException('Le montant dépasse le solde restant dû ('.number_format($balance, 0, ',', ' ').' FCFA).');
        }

        return DB::transaction(function () use ($reservation, $amount, $method, $reference, $recordedBy): Payment {
            $payment = $reservation->payments()->create([
                'user_id' => $recordedBy->id,
                'transaction_id' => 'MAN-'.Str::upper(Str::random(12)),
                'provider' => 'manuel',
                'method' => $method,
                'amount' => $amount,
                'currency' => $reservation->currency ?? 'XOF',
                'statut' => TransactionStatus::Accepted,
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

    /**
     * Rembourse les paiements reçus d'une réservation annulée (ou d'un client absent).
     * La plateforme enregistre le remboursement ; le versement se fait par le moyen de paiement d'origine.
     */
    public function refund(Reservation $reservation): int
    {
        $this->expectStatus($reservation, [ReservationStatus::Cancelled, ReservationStatus::NoShow], 'Seule une réservation annulée ou un client absent peut être remboursé.');

        if ($reservation->amount_paid <= 0) {
            throw new WorkflowException('Aucune somme n’a été réglée sur cette réservation.');
        }

        $amount = $reservation->amount_paid;

        DB::transaction(function () use ($reservation): void {
            $reservation->payments()
                ->where('statut', TransactionStatus::Accepted)
                ->update([
                    'statut' => TransactionStatus::Refunded,
                    'refunded_amount' => DB::raw('amount'),
                    'refunded_at' => now(),
                    'refund_reason' => 'Réservation '.($reservation->statut === ReservationStatus::NoShow ? 'non honorée' : 'annulée'),
                ]);

            $reservation->update([
                'amount_paid' => 0,
                'payment_state' => PaymentState::Refunded,
            ]);
        });

        $this->notifyGuest($reservation, ReservationUpdated::REFUNDED, $amount);

        return $amount;
    }

    /**
     * Rembourse tout ou partie d'un paiement encaissé (geste commercial, erreur de saisie, séjour écourté…).
     * Le solde de la réservation est recalculé ; le client est prévenu.
     */
    public function refundPayment(Payment $payment, int $amount, ?string $reason): void
    {
        if (! $payment->isAccepted()) {
            throw new WorkflowException('Seul un paiement encaissé peut être remboursé.');
        }

        $refundable = $payment->refundableAmount();

        if ($amount < 1 || $amount > $refundable) {
            throw new WorkflowException('Le montant doit être compris entre 1 et '.number_format($refundable, 0, ',', ' ').' FCFA.');
        }

        $reservation = $payment->reservation;

        DB::transaction(function () use ($payment, $amount, $reason, $reservation): void {
            $refunded = $payment->refunded_amount + $amount;

            $payment->update([
                'refunded_amount' => $refunded,
                'statut' => $refunded >= $payment->amount ? TransactionStatus::Refunded : TransactionStatus::Accepted,
                'refunded_at' => now(),
                'refund_reason' => $reason,
            ]);

            $paid = max(0, $reservation->amount_paid - $amount);

            $reservation->update([
                'amount_paid' => $paid,
                'payment_state' => match (true) {
                    $paid === 0 => PaymentState::Refunded,
                    $paid >= $reservation->total_amount => PaymentState::Paid,
                    default => PaymentState::Partial,
                },
            ]);
        });

        $this->notifyGuest($reservation, ReservationUpdated::REFUNDED, $amount);
    }

    /*
    |--------------------------------------------------------------------------
    | OUTILS
    |--------------------------------------------------------------------------
    */

    /**
     * Actions disponibles sur une réservation pour la vue (les droits sont vérifiés à part, par la policy).
     *
     * @return array{confirm: bool, cancel: bool, complete: bool, noShow: bool, payment: bool, refund: bool}
     */
    public function availableActions(Reservation $reservation): array
    {
        $status = $reservation->statut;

        return [
            'confirm' => $status === ReservationStatus::Pending,
            'cancel' => in_array($status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true),
            'complete' => $status === ReservationStatus::Confirmed && ! $reservation->check_out->isFuture(),
            'noShow' => $status === ReservationStatus::Confirmed && ! $reservation->check_in->isFuture(),
            'payment' => $status !== ReservationStatus::Cancelled && $status !== ReservationStatus::NoShow && $reservation->balanceDue() > 0,
            'refund' => in_array($status, [ReservationStatus::Cancelled, ReservationStatus::NoShow], true) && $reservation->amount_paid > 0,
        ];
    }

    /**
     * Email au client : son compte, ou à défaut l'adresse saisie lors de la réservation.
     */
    private function notifyGuest(Reservation $reservation, string $event, ?int $amount = null): void
    {
        $notification = new ReservationUpdated($reservation, $event, $amount);

        if ($reservation->user) {
            $reservation->user->notify($notification);
        } elseif (filled($reservation->guest_email)) {
            Notification::route('mail', $reservation->guest_email)->notify($notification);
        }
    }

    /**
     * @param  list<ReservationStatus>  $allowed
     */
    private function expectStatus(Reservation $reservation, array $allowed, string $message): void
    {
        if (! in_array($reservation->statut, $allowed, true)) {
            throw new WorkflowException($message);
        }
    }
}
