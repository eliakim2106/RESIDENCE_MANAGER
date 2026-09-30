<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Refused = 'refused';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En cours',
            self::Accepted => 'Accepté',
            self::Refused => 'Refusé',
            self::Cancelled => 'Annulé',
            self::Refunded => 'Remboursé',
        };
    }

    /**
     * Couleur du badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Accepted => 'green',
            self::Refused => 'red',
            self::Cancelled => 'gray',
            self::Refunded => 'blue',
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
