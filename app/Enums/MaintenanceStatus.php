<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Done = 'done';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planifiée',
            self::InProgress => 'En cours',
            self::Done => 'Terminée',
        };
    }

    /**
     * Couleur du badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Planned => 'amber',
            self::InProgress => 'blue',
            self::Done => 'green',
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
