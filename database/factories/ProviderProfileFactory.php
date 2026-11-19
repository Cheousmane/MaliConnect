<?php

namespace Database\Factories;

use App\Enums\ProfileStatus;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProviderProfile>
 */
class ProviderProfileFactory extends Factory
{
    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessName = fake()->unique()->company();

        return [
            'user_id' => User::factory(),
            'business_id' => null,
            'category_id' => Category::factory(),
            'subcategory_id' => null,
            'business_name' => $businessName,
            'slug' => Str::slug($businessName).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => 'Prestation de qualité pour vos besoins à Bamako.',
            'logo_path' => null,
            'phone' => '+223'.fake()->numberBetween(60000000, 79999999),
            'whatsapp' => '+223'.fake()->numberBetween(60000000, 79999999),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetAddress(),
            'city' => 'Bamako',
            'commune' => fake()->randomElement(['I', 'II', 'III', 'IV', 'V', 'VI']),
            'quartier' => fake()->randomElement(['Medina', 'Niamakala', 'Badalabougou', 'Koulouba']),
            'latitude' => fake()->latitude(12.4, 12.8),
            'longitude' => fake()->longitude(-8.2, -7.8),
            'years_experience' => fake()->numberBetween(1, 25),
            'response_time_hours' => fake()->numberBetween(1, 48),
            'status' => ProfileStatus::Pending,
            'submitted_at' => now(),
            'verified_at' => null,
            'refusal_reason' => null,
            'is_active' => true,
            'is_featured' => false,
            'rating_average' => 0,
            'rating_count' => 0,
        ];
    }

    /**
     * Indique que le profil est vérifié par l'administration.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProfileStatus::Verified,
            'verified_at' => now(),
        ]);
    }

    /**
     * Indique que le profil attend une vérification.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProfileStatus::Pending,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indique que le profil a été refusé.
     */
    public function refused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProfileStatus::Refused,
            'refusal_reason' => 'Intervention professionnelle et soignée.',
        ]);
    }

    /**
     * Indique que le profil est suspendu.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProfileStatus::Suspended,
        ]);
    }

    /**
     * Associe le profil à un utilisateur existant.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
