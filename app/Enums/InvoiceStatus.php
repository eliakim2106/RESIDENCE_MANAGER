<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'À payer',
            self::Paid => 'Payée',
            self::Cancelled => 'Annulée',
        };
    }

    /**
     * Ton de la pastille dans l'administration.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Paid => 'good',
            self::Unpaid => 'warning',
            self::Cancelled => 'neutral',
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
