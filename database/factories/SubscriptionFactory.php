<?php

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_profile_id' => ProviderProfile::factory(),
            'business_id' => null,
            'plan_id' => null,
            'pending_plan_id' => null,
            'pending_billing_cycle' => null,
            'pending_plan_effective_at' => null,
            'plan' => 'pro',
            'billing_cycle' => BillingCycle::Monthly,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
            'activated_at' => now()->subMonth(),
            'trial_ends_at' => null,
            'grace_ends_at' => null,
            'suspended_at' => null,
            'cancelled_at' => null,
            'cancel_at_period_end' => false,
            'cancellation_reason' => null,
            'renewed_at' => null,
            'last_payment_at' => null,
            'amount' => 5000,
            'currency' => 'XOF',
            'notes' => null,
        ];
    }

    /**
     * Indique que l'abonnement est actif.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
            'activated_at' => now()->subMonth(),
        ]);
    }

    /**
     * Indique que l'abonnement est en période d'essai.
     */
    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Trialing,
            'starts_at' => now(),
            'ends_at' => now()->addDays(14),
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    /**
     * Indique que l'abonnement est en attente.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Pending,
            'starts_at' => null,
            'ends_at' => null,
            'activated_at' => null,
        ]);
    }

    /**
     * Indique que l'abonnement est expiré.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Expired,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(),
            'activated_at' => now()->subMonths(2),
        ]);
    }

    /**
     * Indique que l'abonnement est en retard de paiement.
     */
    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::PastDue,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
            'grace_ends_at' => now()->addDays(7),
            'activated_at' => now()->subMonth(),
        ]);
    }

    /**
     * Indique que l'abonnement est suspendu.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }

    /**
     * Associe l'abonnement à un profil existant.
     */
    public function forProfile(ProviderProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_profile_id' => $profile->id,
            'business_id' => null,
        ]);
    }

    /**
     * Associe l'abonnement à une entreprise.
     */
    public function forBusiness(Business $business): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_profile_id' => null,
            'business_id' => $business->id,
        ]);
    }

    /**
     * Associe l'abonnement à un plan configurable.
     */
    public function forPlan(Plan $plan, BillingCycle $cycle = BillingCycle::Monthly): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $plan->id,
            'plan' => $plan->code,
            'billing_cycle' => $cycle,
            'amount' => $plan->priceFor($cycle),
            'currency' => $plan->currency,
        ]);
    }
}
