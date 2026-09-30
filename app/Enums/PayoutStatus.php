<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Reversé',
            self::Cancelled => 'Annulé',
        };
    }

    /**
     * Ton de la pastille dans l'administration.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Paid => 'good',
            self::Cancelled => 'neutral',
        };
    }
}
