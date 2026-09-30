<?php

namespace Database\Factories;

use App\Models\UnitType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UnitType>
 */
class UnitTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Chambre simple', 'Chambre double', 'Suite', 'Studio', 'Appartement 2 pièces', 'Appartement 3 pièces']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => 'bed',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
