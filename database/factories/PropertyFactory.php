<?php

namespace Database\Factories;

use App\Enums\CancellationPolicy;
use App\Enums\PropertyStatus;
use App\Models\City;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Résidence', 'Hôtel', 'Villa', 'Les Appartements']).' '.fake()->unique()->lastName();

        return [
            'owner_id' => User::factory()->owner(),
            'property_type_id' => PropertyType::factory(),
            'city_id' => City::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'short_description' => fake()->sentence(15),
            'description' => fake()->paragraphs(3, true),
            'address' => fake()->streetAddress(),
            'district' => fake()->randomElement(['Cocody', 'Plateau', 'Marcory', 'Riviera', 'Zone 4', 'Deux-Plateaux']),
            'latitude' => fake()->latitude(5.2, 5.45),
            'longitude' => fake()->longitude(-4.1, -3.9),
            'star_rating' => fake()->optional()->numberBetween(2, 5),
            'phone' => '+225 07 '.fake()->numerify('## ## ## ##'),
            'email' => fake()->safeEmail(),
            'check_in_from' => '14:00',
            'check_out_until' => '12:00',
            'cancellation_policy' => fake()->randomElement(CancellationPolicy::cases()),
            'house_rules' => 'Pas de bruit après 22 h. Pièce d\'identité exigée à l\'arrivée.',
            'allows_pets' => fake()->boolean(20),
            'status' => PropertyStatus::Published,
            'is_featured' => fake()->boolean(25),
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PropertyStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PropertyStatus::Pending,
            'published_at' => null,
        ]);
    }
}
