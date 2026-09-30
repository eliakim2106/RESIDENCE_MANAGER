<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Confirmed => 'Confirmée',
            self::Cancelled => 'Annulée',
            self::Completed => 'Terminée',
            self::NoShow => 'Non présenté',
        };
    }

    /**
     * Couleur du badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'green',
            self::Cancelled => 'red',
            self::Completed => 'blue',
            self::NoShow => 'gray',
        };
    }

    /**
     * Ton de la pastille de statut (status-good, status-info…) dans l'administration.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Confirmed => 'good',
            self::Completed => 'info',
            self::Pending => 'warning',
            self::Cancelled => 'critical',
            self::NoShow => 'neutral',
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
