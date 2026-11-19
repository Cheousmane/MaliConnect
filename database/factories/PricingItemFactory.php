<?php

namespace Database\Factories;

use App\Enums\PricingType;
use App\Models\PricingItem;
use App\Models\PricingSheet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingItem>
 */
class PricingItemFactory extends Factory
{
    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pricing_sheet_id' => PricingSheet::factory(),
            'service_id' => null,
            'category_id' => null,
            'name' => 'Prestation '.fake()->unique()->numberBetween(1, 999),
            'description' => 'Prestation professionnelle réalisée à Bamako.',
            'pricing_type' => PricingType::Fixed,
            'price' => 25000,
            'price_min' => null,
            'price_max' => null,
            'billing_unit' => 'par prestation',
            'conditions' => null,
            'estimated_duration_minutes' => 60,
            'is_available' => true,
            'requires_diagnostic' => false,
            'sort_order' => 0,
        ];
    }

    /**
     * Indique une prestation sur devis, sans montant fictif.
     */
    public function quote(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => PricingType::Quote,
            'price' => null,
            'price_min' => null,
            'price_max' => null,
        ]);
    }

    /**
     * Indique une prestation dont le prix dépend d'un diagnostic.
     */
    public function diagnostic(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => PricingType::Quote,
            'price' => null,
            'price_min' => null,
            'price_max' => null,
            'requires_diagnostic' => true,
        ]);
    }

    /**
     * Indique une fourchette de prix.
     */
    public function range(int $min = 15000, int $max = 40000): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => null,
            'price_min' => $min,
            'price_max' => $max,
        ]);
    }

    /**
     * Indique une prestation momentanément indisponible.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }
}
