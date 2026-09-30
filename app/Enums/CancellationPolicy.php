<?php

namespace App\Enums;

enum CancellationPolicy: string
{
    case Flexible = 'flexible';
    case Moderate = 'moderate';
    case Strict = 'strict';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::Flexible => "Flexible (annulation gratuite jusqu'à 24 h avant)",
            self::Moderate => "Modérée (annulation gratuite jusqu'à 5 jours avant)",
            self::Strict => 'Stricte (non remboursable)',
        };
    }

    /**
     * Nombre d'heures avant l'arrivée jusqu'auquel l'annulation est gratuite (null = jamais).
     */
    public function freeCancellationHours(): ?int
    {
        return match ($this) {
            self::Flexible => 24,
            self::Moderate => 120,
            self::Strict => null,
        };
    }

    /**
     * Couleur du badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Flexible => 'green',
            self::Moderate => 'amber',
            self::Strict => 'red',
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
