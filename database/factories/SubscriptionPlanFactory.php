<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Formule '.fake()->unique()->word(),
            'description' => fake()->sentence(),
            'monthly_price' => 15000,
            'yearly_price' => 150000,
            'max_properties' => 1,
            'max_units' => 5,
            'trial_days' => 30,
            'commission_rate' => 0,
            'features' => ['Calendrier d’occupation', 'Paiements en ligne'],
            'is_featured' => false,
            'position' => 0,
            'statut' => ActiveStatus::Active,
        ];
    }
}
