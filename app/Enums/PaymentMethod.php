<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case MobileMoney = 'mobile_money';
    case Card = 'card';
    case Wallet = 'wallet';
    case Cash = 'cash';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::MobileMoney => 'Mobile Money',
            self::Card => 'Carte bancaire',
            self::Wallet => 'Portefeuille électronique',
            self::Cash => 'Espèces sur place',
        };
    }

    /**
     * Couleur du badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::MobileMoney => 'orange',
            self::Card => 'blue',
            self::Wallet => 'purple',
            self::Cash => 'green',
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
