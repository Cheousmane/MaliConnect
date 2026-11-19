<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'professional_id' => fn (array $attributes) => Booking::find($attributes['booking_id'])->professional_id,
            'customer_id' => fn (array $attributes) => Booking::find($attributes['booking_id'])->customer_id,
            'rating' => fake()->numberBetween(1, 5),
            'comment' => 'Prestation de qualité pour vos besoins à Bamako.',
            'is_approved' => false,
            'professional_reply' => null,
            'replied_at' => null,
        ];
    }

    /**
     * Indique que l'avis a été approuvé.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
        ]);
    }

    /**
     * Indique que le professionnel a répondu à l'avis.
     */
    public function replied(): static
    {
        return $this->state(fn (array $attributes) => [
            'professional_reply' => 'Prestation de qualité pour vos besoins à Bamako.',
            'replied_at' => now(),
        ]);
    }
}
