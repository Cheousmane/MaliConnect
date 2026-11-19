<?php

namespace App\Notifications;

use App\Models\Subscription;

/**
 * Notifie le propriétaire de l'activation ou du renouvellement de son abonnement.
 */
class SubscriptionActivatedNotification extends ApplicationNotification
{
    public function __construct(private readonly Subscription $subscription) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Abonnement activé',
            'description' => sprintf(
                'Votre abonnement %s est actif jusqu’au %s. Toutes les fonctionnalités de votre formule sont disponibles.',
                $this->subscription->planModel?->name ?? $this->subscription->plan,
                $this->formatDate($this->subscription->ends_at),
            ),
            'tone' => 'success',
            'icon' => 'badge-check',
            'url' => route('pro.subscription.index'),
            'resource' => ['type' => 'subscription', 'id' => $this->subscription->id],
        ];
    }
}
