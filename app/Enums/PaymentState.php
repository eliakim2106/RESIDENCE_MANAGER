<?php

namespace App\Enums;

enum PaymentState: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Refunded = 'refunded';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Non payée',
            self::Partial => 'Acompte versé',
            self::Paid => 'Payée',
            self::Refunded => 'Remboursée',
        };
    }

    /**
     * Couleur du badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'red',
            self::Partial => 'amber',
            self::Paid => 'green',
            self::Refunded => 'gray',
        };
    }

    /**
     * Ton de la pastille de statut (status-good, status-info…) dans l'administration.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Paid => 'good',
            self::Refunded => 'info',
            self::Partial => 'warning',
            self::Unpaid => 'critical',
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
