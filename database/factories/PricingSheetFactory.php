<?php

namespace Database\Factories;

use App\Enums\PricingSheetStatus;
use App\Models\PricingSheet;
use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PricingSheet>
 */
class PricingSheetFactory extends Factory
{
    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_profile_id' => ProviderProfile::factory(),
            'category_id' => null,
            'name' => 'Fiche tarifaire '.fake()->unique()->numberBetween(1, 999),
            'public_token' => (string) Str::uuid(),
            'description' => 'Tarifs indicatifs pour les prestations courantes.',
            'currency' => 'XOF',
            'general_conditions' => 'Les tarifs sont exprimés en FCFA et peuvent varier selon la complexité du chantier.',
            'status' => PricingSheetStatus::Draft,
            'valid_until' => null,
            'published_at' => null,
            'archived_at' => null,
        ];
    }

    /**
     * Indique que la fiche est publiée.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PricingSheetStatus::Published,
            'published_at' => now(),
        ]);
    }

    /**
     * Indique que la fiche est archivée.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PricingSheetStatus::Archived,
            'archived_at' => now(),
        ]);
    }

    /**
     * Indique que la fiche est expirée.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_until' => now()->subDay()->toDateString(),
        ]);
    }

    /**
     * Associe la fiche à un profil existant.
     */
    public function forProfile(ProviderProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_profile_id' => $profile->id,
            'category_id' => $profile->category_id,
        ]);
    }
}
