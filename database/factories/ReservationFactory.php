<?php

namespace Database\Factories;

use App\Enums\CancellationPolicy;
use App\Enums\PaymentState;
use App\Enums\ReservationStatus;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 day', '+2 months');
        $nights = fake()->numberBetween(1, 7);
        $subtotal = $nights * fake()->numberBetween(10, 60) * 1000;
        $serviceFee = (int) round($subtotal * 0.05);

        return [
            'user_id' => User::factory(),
            'property_id' => Property::factory(),
            'check_in' => $checkIn,
            'check_out' => (clone $checkIn)->modify("+{$nights} days"),
            'nights' => $nights,
            'adults' => fake()->numberBetween(1, 3),
            'children' => fake()->numberBetween(0, 2),
            'subtotal' => $subtotal,
            'service_fee' => $serviceFee,
            'total_amount' => $subtotal + $serviceFee,
            'currency' => 'XOF',
            'statut' => ReservationStatus::Pending,
            'payment_state' => PaymentState::Unpaid,
            'cancellation_policy' => CancellationPolicy::Flexible,
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'guest_phone' => '+225 07 '.fake()->numerify('## ## ## ##'),
            'special_requests' => fake()->optional(0.3)->sentence(),
            'expires_at' => now()->addMinutes(30),
        ];
    }

    /**
     * Demande à valider, avec un délai de paiement encore ouvert.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'statut' => ReservationStatus::Pending,
            'payment_state' => PaymentState::Unpaid,
            'amount_paid' => 0,
            'expires_at' => now()->addDays(2),
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'statut' => ReservationStatus::Confirmed,
            'payment_state' => PaymentState::Paid,
            'amount_paid' => $attributes['total_amount'],
            'confirmed_at' => now(),
            'expires_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes): array {
            $checkIn = fake()->dateTimeBetween('-6 months', '-10 days');

            return [
                'check_in' => $checkIn,
                'check_out' => (clone $checkIn)->modify("+{$attributes['nights']} days"),
                'statut' => ReservationStatus::Completed,
                'payment_state' => PaymentState::Paid,
                'amount_paid' => $attributes['total_amount'],
                'confirmed_at' => $checkIn,
                'expires_at' => null,
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'statut' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Changement de programme',
            'expires_at' => null,
        ]);
    }
}
