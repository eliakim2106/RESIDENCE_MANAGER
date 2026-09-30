<?php

namespace Database\Factories;

use App\Models\Unit;
use App\Models\UnitImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitImage>
 */
class UnitImageFactory extends Factory
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
            'path' => 'https://picsum.photos/seed/'.fake()->uuid().'/1200/800',
            'caption' => fake()->optional()->sentence(4),
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
