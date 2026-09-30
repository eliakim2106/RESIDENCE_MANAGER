<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Owner = 'owner';
    case Client = 'client';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super administrateur',
            self::Admin => 'Administrateur',
            self::Owner => 'Propriétaire',
            self::Client => 'Client',
        };
    }

    /**
     * Couleur du badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::SuperAdmin => 'purple',
            self::Admin => 'indigo',
            self::Owner => 'blue',
            self::Client => 'gray',
        };
    }

    /**
     * Ton de la pastille (status-good, status-info…) dans l'administration.
     */
    public function tone(): string
    {
        return match ($this) {
            self::SuperAdmin, self::Admin => 'info',
            self::Owner => 'warning',
            self::Client => 'neutral',
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
