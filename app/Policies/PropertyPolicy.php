<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

/**
 * Les administrateurs gèrent tous les établissements, un propriétaire uniquement les siens.
 * Les unités suivent les droits de leur établissement.
 */
class PropertyPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, Property $property): bool
    {
        return $this->owns($user, $property);
    }

    /**
     * Publier directement, valider, refuser ou suspendre : administrateurs uniquement (accordé par before()).
     */
    public function moderate(User $user, Property $property): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Property $property): bool
    {
        return $this->owns($user, $property);
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->owns($user, $property);
    }

    private function owns(User $user, Property $property): bool
    {
        return $user->isOwner() && $property->owner_id === $user->id;
    }
}
