<?php

namespace Database\Factories;

use App\Models\Availability;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'date' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'price' => null,
            'blocked_quantity' => 0,
            'is_closed' => false,
            'note' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_closed' => true,
            'note' => 'Fermeture',
        ]);
    }
}
