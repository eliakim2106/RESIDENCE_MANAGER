<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Suspended = 'suspended';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Pending => 'En attente',
            self::Suspended => 'Suspendu',
        };
    }

    /**
     * Couleur du badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Pending => 'amber',
            self::Suspended => 'red',
        };
    }

    /**
     * Ton de la pastille (status-good, status-info…) dans l'administration.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Active => 'good',
            self::Pending => 'warning',
            self::Suspended => 'critical',
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
