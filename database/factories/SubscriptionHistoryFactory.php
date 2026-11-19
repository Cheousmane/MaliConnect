<?php

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionHistory>
 */
class SubscriptionHistoryFactory extends Factory
{
    protected $model = SubscriptionHistory::class;

    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'actor_id' => null,
            'event' => 'activated',
            'from_status' => null,
            'to_status' => 'active',
            'from_plan_id' => null,
            'to_plan_id' => null,
            'billing_cycle' => BillingCycle::Monthly,
            'amount' => 5000,
            'currency' => 'XOF',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'note' => null,
            'metadata' => null,
        ];
    }
}
