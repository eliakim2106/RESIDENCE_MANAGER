<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
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
            'user_id' => User::factory(),
            'property_id' => Property::factory(),
            'rating' => fake()->numberBetween(6, 10),
            'cleanliness' => fake()->numberBetween(6, 10),
            'comfort' => fake()->numberBetween(6, 10),
            'location' => fake()->numberBetween(6, 10),
            'staff' => fake()->numberBetween(6, 10),
            'value_for_money' => fake()->numberBetween(6, 10),
            'title' => fake()->randomElement(['Séjour parfait', 'Très bon accueil', 'Bon rapport qualité-prix', 'Je recommande', 'Agréable séjour']),
            'comment' => fake()->paragraph(),
            'statut' => ReviewStatus::Approved,
        ];
    }
}
