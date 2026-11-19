<?php

namespace Database\Factories;

use App\Enums\ServiceRequestStatus;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'provider_profile_id' => ProviderProfile::factory(),
            'service_id' => null,
            'category_id' => null,
            'title' => 'Demande de devis '.fake()->unique()->numberBetween(1, 999),
            'description' => 'Prestation de qualité pour vos besoins à Bamako.',
            'address' => fake()->streetAddress(),
            'city' => 'Bamako',
            'commune' => fake()->randomElement(['I', 'II', 'III', 'IV', 'V', 'VI']),
            'quartier' => fake()->randomElement(['Medina', 'Niamakala', 'Badalabougou']),
            'budget_min' => 10000,
            'budget_max' => 50000,
            'desired_date' => now()->addDays(7),
            'preferred_time' => 'Matin',
            'status' => ServiceRequestStatus::Pending,
        ];
    }

    /**
     * Indique que la demande a reçu un devis.
     */
    public function quoted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceRequestStatus::Quoted,
            'responded_at' => now(),
        ]);
    }

    /**
     * Indique que la demande a été acceptée.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceRequestStatus::Accepted,
            'responded_at' => now(),
        ]);
    }

    /**
     * Associe la demande à un client existant.
     */
    public function forCustomer(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $user->id,
        ]);
    }
}
