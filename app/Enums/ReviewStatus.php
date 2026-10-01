<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Approved => 'Publié',
            self::Rejected => 'Masqué',
        };
    }

    /**
     * Ton de la pastille de statut dans l'administration (status-pill status-…).
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'good',
            self::Rejected => 'neutral',
        };
    }

    /**
     * Couleur du badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
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
