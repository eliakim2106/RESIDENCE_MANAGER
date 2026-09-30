<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyImage>
 */
class PropertyImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'path' => 'https://picsum.photos/seed/'.fake()->uuid().'/1200/800',
            'caption' => fake()->optional()->sentence(4),
            'is_cover' => false,
            'position' => fake()->numberBetween(0, 10),
        ];
    }

    public function cover(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_cover' => true,
            'position' => 0,
        ]);
    }
}
