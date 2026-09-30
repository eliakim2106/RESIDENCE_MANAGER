<?php

namespace App\Services;

use App\Enums\PropertyStatus;
use App\Enums\UserRole;
use App\Exceptions\WorkflowException;
use App\Models\Property;
use App\Models\User;
use App\Notifications\PropertyModerated;
use App\Notifications\PropertySubmitted;
use Illuminate\Support\Facades\Notification;

/**
 * Validation des établissements.
 *
 * Un propriétaire soumet son établissement (brouillon → en attente) ; un administrateur l'approuve (→ publié),
 * le refuse avec un motif (→ brouillon), le suspend avec un motif (publié → suspendu) ou le rétablit (→ publié).
 * Un administrateur qui enregistre le formulaire publie directement.
 */
class PropertyModeration
{
    /**
     * Applique le choix « en ligne / brouillon » du formulaire d'établissement.
     */
    public function applyVisibility(Property $property, User $by, bool $online): void
    {
        $status = $property->statut ?? PropertyStatus::Draft;

        if ($by->isAdmin()) {
            match (true) {
                ! $online => $property->update(['statut' => PropertyStatus::Draft]),
                $status === PropertyStatus::Pending => $this->approve($property, $by),
                $status === PropertyStatus::Suspended => $this->reinstate($property, $by),
                default => $this->publish($property, $by),
            };

            return;
        }

        // Seul un administrateur lève une suspension
        if ($status === PropertyStatus::Suspended) {
            return;
        }

        if (! $online) {
            $property->update(['statut' => PropertyStatus::Draft]);

            return;
        }

        if ($status === PropertyStatus::Draft) {
            $this->submit($property);
        }
    }

    public function submit(Property $property): void
    {
        $this->expectStatus($property, [PropertyStatus::Draft], 'Seul un brouillon peut être soumis à validation.');

        $property->update([
            'statut' => PropertyStatus::Pending,
            'submitted_at' => now(),
        ]);

        Notification::send(
            User::query()->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])->get(),
            new PropertySubmitted($property),
        );
    }

    public function approve(Property $property, User $admin): void
    {
        $this->expectStatus($property, [PropertyStatus::Pending], 'Cet établissement n’est pas en attente de validation.');

        $this->publish($property, $admin);
        $this->notifyOwner($property, PropertyModerated::APPROVED);
    }

    public function reject(Property $property, User $admin, string $reason): void
    {
        $this->expectStatus($property, [PropertyStatus::Pending], 'Cet établissement n’est pas en attente de validation.');

        $this->decide($property, $admin, PropertyStatus::Draft, $reason);
        $this->notifyOwner($property, PropertyModerated::REJECTED);
    }

    public function suspend(Property $property, User $admin, string $reason): void
    {
        $this->expectStatus($property, [PropertyStatus::Published], 'Seul un établissement publié peut être suspendu.');

        $this->decide($property, $admin, PropertyStatus::Suspended, $reason);
        $this->notifyOwner($property, PropertyModerated::SUSPENDED);
    }

    public function reinstate(Property $property, User $admin): void
    {
        $this->expectStatus($property, [PropertyStatus::Suspended], 'Cet établissement n’est pas suspendu.');

        $this->publish($property, $admin);
        $this->notifyOwner($property, PropertyModerated::REINSTATED);
    }

    /*
    |--------------------------------------------------------------------------
    | OUTILS
    |--------------------------------------------------------------------------
    */

    private function publish(Property $property, User $admin): void
    {
        $property->update([
            'statut' => PropertyStatus::Published,
            'published_at' => $property->published_at ?? now(),
            'moderation_note' => null,
            'moderated_at' => now(),
            'moderated_by' => $admin->id,
        ]);
    }

    private function decide(Property $property, User $admin, PropertyStatus $status, string $reason): void
    {
        $property->update([
            'statut' => $status,
            'moderation_note' => $reason,
            'moderated_at' => now(),
            'moderated_by' => $admin->id,
        ]);
    }

    private function notifyOwner(Property $property, string $decision): void
    {
        $property->owner?->notify(new PropertyModerated($property, $decision));
    }

    /**
     * @param  list<PropertyStatus>  $allowed
     */
    private function expectStatus(Property $property, array $allowed, string $message): void
    {
        if (! in_array($property->statut, $allowed, true)) {
            throw new WorkflowException($message);
        }
    }
}
