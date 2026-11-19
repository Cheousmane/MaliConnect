<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Professional>
 */
class ProfessionalFactory extends Factory
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
            'category_id' => Category::factory(),
            'subcategory_id' => null,
            'business_name' => $businessName,
            'slug' => Str::slug($businessName).'-'.fake()->unique()->numberBetween(1, 999999),
            'bio' => 'Prestation de qualité pour vos besoins à Bamako.',
            'phone' => '+223 '.fake()->numerify('## ## ## ##'),
            'whatsapp' => '+223 '.fake()->numerify('## ## ## ##'),
            'address' => fake()->streetAddress(),
            'city' => 'Bamako',
            'commune' => fake()->randomElement(['I', 'II', 'III', 'IV', 'V', 'VI']),
            'latitude' => fake()->latitude(12.4, 12.8),
            'longitude' => fake()->longitude(-8.2, -7.8),
            'avatar' => null,
            'cover_image' => null,
            'years_experience' => fake()->numberBetween(1, 25),
            'response_time_hours' => fake()->numberBetween(1, 48),
            'status' => Professional::STATUS_PENDING,
            'is_verified' => false,
            'is_active' => true,
            'is_featured' => false,
            'rating_average' => 0,
            'rating_count' => 0,
            'subscription_plan' => Professional::PLAN_FREE,
            'subscription_expires_at' => null,
        ];
    }

    /**
     * Indique que le professionnel est visible dans le catalogue public.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Professional::STATUS_ACTIVE,
            'is_active' => true,
        ]);
    }

    /**
     * Indique que le professionnel a été vérifié par la plateforme.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Indique que le professionnel est mis en avant sur la page d'accueil.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indique que le professionnel est suspendu.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Professional::STATUS_SUSPENDED,
        ]);
    }

    /**
     * Associe le professionnel à un utilisateur existant.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Associe le professionnel à une catégorie existante.
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }
}
