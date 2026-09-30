<?php

namespace Database\Factories;

use App\Enums\MaintenanceStatus;
use App\Models\Maintenance;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Maintenance>
 */
class MaintenanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('now', '+1 month');

        return [
            'unit_id' => Unit::factory(),
            'title' => fake()->randomElement(['Peinture', 'Réparation climatisation', 'Plomberie', 'Remplacement literie']),
            'description' => fake()->sentence(),
            'starts_on' => $startsOn,
            'ends_on' => (clone $startsOn)->modify('+'.fake()->numberBetween(1, 5).' days'),
            'quantity' => 1,
            'status' => MaintenanceStatus::Planned,
        ];
    }
}
