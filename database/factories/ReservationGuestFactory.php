<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\ReservationGuest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationGuest>
 */
class ReservationGuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'full_name' => fake()->name(),
            'is_child' => false,
            'nationality' => 'CI',
        ];
    }
}
