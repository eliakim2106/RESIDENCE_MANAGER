<?php

namespace Database\Factories;

use App\Models\Unit;
use App\Models\UnitRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitRate>
 */
class UnitRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('now', '+3 months');

        return [
            'unit_id' => Unit::factory(),
            'name' => fake()->randomElement(['Haute saison', 'Fêtes de fin d\'année', 'Vacances de Pâques', 'Basse saison']),
            'starts_on' => $startsOn,
            'ends_on' => (clone $startsOn)->modify('+'.fake()->numberBetween(7, 30).' days'),
            'price' => fake()->numberBetween(8, 80) * 1000,
            'min_nights' => fake()->optional()->numberBetween(2, 3),
        ];
    }
}
