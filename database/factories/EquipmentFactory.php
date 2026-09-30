<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Enums\EquipmentCategory;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name),
            'icon' => 'check',
            'category' => fake()->randomElement(EquipmentCategory::cases()),
            'is_popular' => fake()->boolean(30),
            'statut' => ActiveStatus::Active,
        ];
    }
}
