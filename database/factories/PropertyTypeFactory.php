<?php

namespace Database\Factories;

use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PropertyType>
 */
class PropertyTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Hôtel', 'Résidence meublée', 'Appartement', 'Villa', 'Maison d\'hôtes', 'Lodge']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => 'building',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
