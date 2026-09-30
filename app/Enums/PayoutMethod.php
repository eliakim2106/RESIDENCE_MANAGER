<?php

namespace App\Enums;

/**
 * Moyen utilisé par DS Holding pour reverser sa part à un propriétaire.
 */
enum PayoutMethod: string
{
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::MobileMoney => 'Mobile Money',
            self::BankTransfer => 'Virement bancaire',
            self::Cash => 'Espèces',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MobileMoney => 'fa-mobile-screen-button',
            self::BankTransfer => 'fa-building-columns',
            self::Cash => 'fa-money-bill-wave',
        };
    }

    /**
     * Libellé du champ « compte » selon le moyen.
     */
    public function accountLabel(): string
    {
        return match ($this) {
            self::MobileMoney => 'Numéro Mobile Money',
            self::BankTransfer => 'RIB / IBAN',
            self::Cash => 'Lieu de remise',
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
