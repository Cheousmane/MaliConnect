<?php

namespace App\Notifications;

use App\Models\Subscription;

/**
 * Rappelle au professionnel que son abonnement arrive à échéance.
 */
class SubscriptionExpiringSoonNotification extends ApplicationNotification
{
    public function __construct(private readonly Subscription $subscription) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Abonnement bientôt expiré',
            'description' => sprintf(
                'Votre abonnement %s expire le %s. Renouvelez-le dès maintenant pour conserver vos fonctionnalités.',
                $this->subscription->planModel?->name ?? $this->subscription->plan,
                $this->formatDate($this->subscription->ends_at),
            ),
            'tone' => 'warning',
            'icon' => 'clock',
            'url' => route('pro.subscription.plans'),
            'resource' => ['type' => 'subscription', 'id' => $this->subscription->id],
        ];
    }
}
