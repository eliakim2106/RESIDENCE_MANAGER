<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationUnit>
 */
class ReservationUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pricePerNight = fake()->numberBetween(10, 60) * 1000;

        return [
            'reservation_id' => Reservation::factory(),
            'unit_id' => Unit::factory(),
            'quantity' => 1,
            'price_per_night' => $pricePerNight,
            'subtotal' => $pricePerNight,
        ];
    }
}
