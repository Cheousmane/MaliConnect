<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'professional_id' => Professional::factory(),
            'service_id' => null,
            'customer_id' => User::factory(),
            'quote_id' => null,
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+2 months'),
            'duration_minutes' => fake()->randomElement([30, 60, 90, 120]),
            'address' => fake()->streetAddress(),
            'notes' => fake()->optional()->passthrough('Intervention rapide et soignée.'),
            'amount' => fake()->numberBetween(10000, 150000),
            'status' => Booking::STATUS_CONFIRMED,
            'cancelled_by' => null,
            'cancellation_reason' => null,
            'cancelled_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indique que la réservation est terminée.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Indique que la réservation a été annulée.
     */
    public function cancelled(string $by = 'customer'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_CANCELLED,
            'cancelled_by' => $by,
            'cancellation_reason' => 'Intervention professionnelle et soignée.',
            'cancelled_at' => now(),
        ]);
    }
}
