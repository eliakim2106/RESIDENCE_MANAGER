<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Gestion des comptes : réservée aux administrateurs.
 * Personne n'agit sur son propre compte depuis cette page (il a « Mon profil »),
 * et seul un super administrateur agit sur un compte administrateur ou change un rôle.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    /**
     * Suspendre ou réactiver le compte.
     */
    public function manage(User $user, User $target): bool
    {
        return $user->isAdmin()
            && ! $user->is($target)
            && ($user->role === UserRole::SuperAdmin || ! $target->isAdmin());
    }

    public function changeRole(User $user, User $target): bool
    {
        return $user->role === UserRole::SuperAdmin && ! $user->is($target);
    }
}
