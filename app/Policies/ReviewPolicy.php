<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

/**
 * Avis des voyageurs.
 * Propriétaire : voit les avis de ses établissements, y répond et peut en signaler un à DS Holding.
 * Administrateurs : voient tous les avis et les modèrent (masquer, republier) ; ils ne répondent pas à la place de l'établissement.
 */
class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOwner();
    }

    public function view(User $user, Review $review): bool
    {
        return $user->isAdmin() || $this->ownsProperty($user, $review);
    }

    /**
     * Répondre (ou modifier sa réponse) : avis publié d'un de ses établissements.
     */
    public function reply(User $user, Review $review): bool
    {
        return $this->ownsProperty($user, $review) && $review->isPublished();
    }

    /**
     * Signaler un avis abusif à DS Holding : une fois, tant qu'il est publié.
     */
    public function report(User $user, Review $review): bool
    {
        return $this->ownsProperty($user, $review) && $review->isPublished() && ! $review->isReported();
    }

    /**
     * Masquer ou republier.
     */
    public function moderate(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }

    private function ownsProperty(User $user, Review $review): bool
    {
        return $user->isOwner() && $review->property?->owner_id === $user->id;
    }
}
