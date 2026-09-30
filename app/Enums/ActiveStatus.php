<?php

namespace App\Enums;

/**
 * Statut marche / arrêt : référentiels (villes, types, équipements) et unités.
 */
enum ActiveStatus: string
{
    case Active = 'actif';
    case Inactive = 'inactif';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Inactive => 'Inactif',
        };
    }

    /**
     * Couleur du badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Inactive => 'gray',
        };
    }

    /**
     * Ton de la pastille (status-good, status-neutral) dans l'administration.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Active => 'good',
            self::Inactive => 'neutral',
        };
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
