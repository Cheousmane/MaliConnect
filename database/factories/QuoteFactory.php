<?php

namespace Database\Factories;

use App\Models\Professional;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
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
            'message' => 'Prestation de qualité pour vos besoins à Bamako.',
            'budget_min' => fake()->numberBetween(10000, 50000),
            'budget_max' => fake()->numberBetween(50001, 150000),
            'desired_date' => fake()->dateTimeBetween('+1 day', '+2 months')->format('Y-m-d'),
            'address' => fake()->streetAddress(),
            'status' => Quote::STATUS_PENDING,
            'professional_message' => null,
            'quoted_amount' => null,
            'quoted_at' => null,
            'expires_at' => null,
        ];
    }

    /**
     * Indique que le professionnel a répondu avec un devis.
     */
    public function quoted(int $amount = 75000): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Quote::STATUS_QUOTED,
            'professional_message' => 'Prestation de qualité pour vos besoins à Bamako.',
            'quoted_amount' => $amount,
            'quoted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Indique que le client a accepté le devis.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Quote::STATUS_ACCEPTED,
        ]);
    }

    /**
     * Indique que le devis a été annulé.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Quote::STATUS_CANCELLED,
        ]);
    }
}
