<?php

namespace App\Enums;

enum EquipmentCategory: string
{
    case General = 'general';
    case Room = 'room';
    case Bathroom = 'bathroom';
    case Kitchen = 'kitchen';
    case Outdoor = 'outdoor';
    case Service = 'service';

    /**
     * Libellé affiché à l'utilisateur.
     */
    public function label(): string
    {
        return match ($this) {
            self::General => 'Général',
            self::Room => 'Chambre',
            self::Bathroom => 'Salle de bain',
            self::Kitchen => 'Cuisine',
            self::Outdoor => 'Extérieur',
            self::Service => 'Services',
        };
    }

    /**
     * Couleur du badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::General => 'gray',
            self::Room => 'blue',
            self::Bathroom => 'cyan',
            self::Kitchen => 'orange',
            self::Outdoor => 'green',
            self::Service => 'purple',
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
