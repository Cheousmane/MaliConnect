<?php

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Business;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'PAY-'.strtoupper(fake()->unique()->bothify('########')),
            'external_reference' => fake()->optional()->bothify('OM-########'),
            'subscription_id' => null,
            'plan_id' => null,
            'billing_cycle' => BillingCycle::Monthly,
            'user_id' => User::factory(),
            'provider_profile_id' => ProviderProfile::factory(),
            'business_id' => null,
            'amount' => fake()->randomElement([5000, 10000, 50000, 100000]),
            'currency' => 'XOF',
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'gateway' => 'manual',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'declared_at' => now()->subHour(),
            'verified_at' => now(),
            'verified_by' => null,
            'notes' => null,
            'rejection_reason' => null,
        ];
    }

    /**
     * Indique que le paiement a été encaissé.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'verified_at' => now(),
        ]);
    }

    /**
     * Indique que le paiement est en attente.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
            'verified_at' => null,
            'declared_at' => now(),
        ]);
    }

    /**
     * Indique que le paiement a été refusé.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Rejected,
            'paid_at' => null,
            'verified_at' => now(),
            'rejection_reason' => 'Preuve illisible ou transaction introuvable.',
        ]);
    }

    /**
     * Indique que le paiement a échoué.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
            'paid_at' => null,
            'verified_at' => null,
        ]);
    }

    /**
     * Indique que le paiement a été remboursé.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Refunded,
            'paid_at' => now()->subDay(),
        ]);
    }

    /**
     * Rattache le paiement à un abonnement.
     */
    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => $subscription->id,
            'provider_profile_id' => $subscription->provider_profile_id,
            'business_id' => $subscription->business_id,
            'plan_id' => $subscription->plan_id,
            'billing_cycle' => $subscription->billing_cycle ?? BillingCycle::Monthly,
        ]);
    }

    /**
     * Rattache le paiement à un profil professionnel.
     */
    public function forProfile(ProviderProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_profile_id' => $profile->id,
            'business_id' => null,
        ]);
    }

    /**
     * Rattache le paiement à une entreprise.
     */
    public function forBusiness(Business $business): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_profile_id' => null,
            'business_id' => $business->id,
        ]);
    }

    /**
     * Rattache le paiement à une formule.
     */
    public function forPlan(Plan $plan, BillingCycle $cycle = BillingCycle::Monthly): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $plan->id,
            'billing_cycle' => $cycle,
            'amount' => $plan->priceFor($cycle),
            'currency' => $plan->currency,
        ]);
    }
}
