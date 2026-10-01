<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

/**
 * Administrateurs : toutes les réservations. Propriétaire : celles de ses établissements, qu'il gère.
 * Client : ses propres réservations, qu'il peut annuler tant que le séjour n'a pas commencé.
 */
class ReservationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $this->manage($user, $reservation) || $this->isGuest($user, $reservation);
    }

    /**
     * Confirmer, clôturer, déclarer une absence, encaisser, annoter.
     */
    public function manage(User $user, Reservation $reservation): bool
    {
        return $user->isOwner() && $reservation->property?->owner_id === $user->id;
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $this->manage($user, $reservation)
            || ($this->isGuest($user, $reservation) && $reservation->isCancellable());
    }

    /**
     * Régler en ligne le solde de sa réservation (client).
     */
    public function pay(User $user, Reservation $reservation): bool
    {
        return $this->isGuest($user, $reservation);
    }

    private function isGuest(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }
}
