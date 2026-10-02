<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitType;

/**
 * Logement entier : établissement sans « Gestion des unités » (un studio, un appartement, une villa louée en entier).
 * Le propriétaire décrit son logement dans le formulaire de l'établissement ; l'unité unique qui porte le prix,
 * la capacité et les disponibilités est créée et tenue à jour ici. Sans photo propre, elle utilise celles de l'établissement.
 */
class WholeUnit
{
    /**
     * L'unité qui représente le logement (la plus ancienne), ou null s'il n'en a pas encore.
     */
    public function of(Property $property): ?Unit
    {
        return $property->units()->with('equipments')->oldest('id')->first();
    }

    /**
     * Crée ou met à jour l'unité unique du logement.
     *
     * @param  array{unit_type_id: int, max_adults: int, bedrooms: int, beds: int, bathrooms: int, size_m2: ?int, base_price: int, promo_price: ?int}  $attributes
     * @param  list<int>  $equipmentIds
     */
    public function sync(Property $property, array $attributes, array $equipmentIds): Unit
    {
        $unit = $this->of($property) ?? $property->units()->make();

        $unit->fill([
            ...$attributes,
            'name' => UnitType::query()->find($attributes['unit_type_id'])?->name ?? 'Logement entier',
            'quantity' => 1,
            'statut' => ActiveStatus::Active,
        ])->save();

        $unit->equipments()->sync($equipmentIds);

        return $unit;
    }
}
