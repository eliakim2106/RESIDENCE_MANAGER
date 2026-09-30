<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Administrateurs : tous les paiements. Propriétaire : ceux de ses établissements, qu'il peut rembourser.
 * Client : le reçu de ses propres paiements.
 */
class PaymentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Consulter le paiement et son reçu.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $this->manage($user, $payment) || $payment->reservation?->user_id === $user->id;
    }

    /**
     * Fiche détaillée et remboursement.
     */
    public function manage(User $user, Payment $payment): bool
    {
        return $user->isOwner() && $payment->reservation?->property?->owner_id === $user->id;
    }
}
