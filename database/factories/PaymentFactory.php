<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'transaction_id' => 'TX'.Str::upper(Str::random(14)),
            'provider' => 'cinetpay',
            'method' => PaymentMethod::MobileMoney,
            'operator' => fake()->randomElement(['OM', 'MOMO', 'FLOOZ', 'WAVE']),
            'amount' => fake()->numberBetween(10, 300) * 1000,
            'currency' => 'XOF',
            'status' => TransactionStatus::Pending,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TransactionStatus::Accepted,
            'operator_reference' => Str::upper(Str::random(10)),
            'paid_at' => now(),
        ]);
    }
}
