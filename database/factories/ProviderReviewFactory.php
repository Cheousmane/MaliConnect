<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\ProviderBooking;
use App\Models\ProviderReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderReview>
 */
class ProviderReviewFactory extends Factory
{
    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_booking_id' => ProviderBooking::factory(),
            'customer_id' => fn (array $attributes) => ProviderBooking::find($attributes['provider_booking_id'])?->customer_id,
            'provider_profile_id' => fn (array $attributes) => ProviderBooking::find($attributes['provider_booking_id'])?->provider_profile_id,
            'rating' => fake()->numberBetween(1, 5),
            'comment' => 'Prestation de qualité pour vos besoins à Bamako.',
            'status' => ReviewStatus::Pending,
            'published_at' => null,
            'professional_reply' => null,
            'replied_at' => null,
        ];
    }

    /**
     * Indique que l'avis a été approuvé et publié.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::Approved,
            'published_at' => now(),
        ]);
    }

    /**
     * Indique que l'avis a été refusé.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::Rejected,
            'published_at' => null,
        ]);
    }

    /**
     * Ajoute la réponse du professionnel.
     */
    public function replied(string $reply = 'Merci pour votre retour.'): static
    {
        return $this->state(fn (array $attributes) => [
            'professional_reply' => $reply,
            'replied_at' => now(),
        ]);
    }
}
