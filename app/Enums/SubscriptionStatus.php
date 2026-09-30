<?php

namespace App\Enums;

/**
 * Cycle de vie d'un abonnement : essai → actif ⇄ en retard → suspendu, ou résilié.
 */
enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Essai gratuit',
            self::Active => 'Actif',
            self::PastDue => 'Paiement attendu',
            self::Suspended => 'Suspendu',
            self::Cancelled => 'Résilié',
        };
    }

    /**
     * Ton de la pastille dans l'administration.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Active => 'good',
            self::Trial => 'info',
            self::PastDue => 'warning',
            self::Suspended => 'critical',
            self::Cancelled => 'neutral',
        };
    }

    /**
     * Les établissements du propriétaire restent en ligne (le retard laisse le délai de grâce courir).
     */
    public function isInGoodStanding(): bool
    {
        return in_array($this, [self::Trial, self::Active, self::PastDue], true);
    }

    /**
     * Statuts « en règle », pour les requêtes.
     *
     * @return list<self>
     */
    public static function goodStanding(): array
    {
        return [self::Trial, self::Active, self::PastDue];
    }

    /**
     * Options pour les listes déroulantes.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}
