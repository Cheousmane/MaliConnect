<?php

namespace Database\Factories;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'legal_name' => fake()->company().' SARL',
            'description' => 'Prestation de qualité pour vos besoins à Bamako.',
            'phone' => '+223'.fake()->numberBetween(60000000, 79999999),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->streetAddress(),
            'city' => 'Bamako',
            'commune' => fake()->randomElement(['I', 'II', 'III', 'IV', 'V', 'VI']),
            'logo_path' => null,
            'status' => BusinessStatus::Pending,
            'submitted_at' => now(),
        ];
    }

    /**
     * Indique que l'entreprise est en attente de vérification.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BusinessStatus::Pending,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indique que l'entreprise est vérifiée.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BusinessStatus::Verified,
            'verified_at' => now(),
        ]);
    }

    /**
     * Associe l'entreprise à un propriétaire existant.
     */
    public function forOwner(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $user->id,
        ]);
    }
}
