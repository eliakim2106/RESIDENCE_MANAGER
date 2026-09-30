<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'region' => fake()->randomElement(['Abidjan', 'Sud-Comoé', 'Gbêkê', 'San-Pédro', 'Poro']),
            'country' => 'CI',
            'statut' => ActiveStatus::Active,
        ];
    }
}
