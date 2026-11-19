<?php

namespace Database\Factories;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
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
            'name' => 'Service '.fake()->unique()->numberBetween(1, 999),
            'description' => 'Prestation de qualité pour vos besoins à Bamako.',
            'price_min' => null,
            'price_max' => null,
            'price_type' => Service::PRICE_TYPE_QUOTE,
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90, 120]),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    /**
     * Indique que le service a un prix fixe.
     */
    public function fixedPrice(int $price = 25000): static
    {
        return $this->state(fn (array $attributes) => [
            'price_type' => Service::PRICE_TYPE_FIXED,
            'price_min' => $price,
            'price_max' => null,
        ]);
    }

    /**
     * Indique que le service démarre à un prix donné.
     */
    public function startingAt(int $price = 15000): static
    {
        return $this->state(fn (array $attributes) => [
            'price_type' => Service::PRICE_TYPE_FROM,
            'price_min' => $price,
            'price_max' => null,
        ]);
    }

    /**
     * Indique que le service est inactif.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
