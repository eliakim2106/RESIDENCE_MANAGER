<?php

namespace Database\Factories;

use App\Enums\UnitStatus;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Chambre Deluxe', 'Suite Junior', 'Studio Confort', 'Appartement Standing', 'Chambre Supérieure']).' '.fake()->unique()->numberBetween(1, 9999);
        $basePrice = fake()->numberBetween(5, 60) * 1000;

        return [
            'property_id' => Property::factory(),
            'unit_type_id' => UnitType::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'max_adults' => fake()->numberBetween(1, 4),
            'max_children' => fake()->numberBetween(0, 2),
            'bedrooms' => fake()->numberBetween(1, 3),
            'beds' => fake()->numberBetween(1, 3),
            'bathrooms' => fake()->numberBetween(1, 2),
            'size_m2' => fake()->numberBetween(18, 120),
            'quantity' => fake()->numberBetween(1, 5),
            'base_price' => $basePrice,
            'weekend_price' => fake()->boolean(40) ? (int) ($basePrice * 1.2) : null,
            'cleaning_fee' => fake()->randomElement([0, 2000, 5000]),
            'min_nights' => 1,
            'status' => UnitStatus::Active,
        ];
    }
}
