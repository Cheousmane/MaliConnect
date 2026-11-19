<?php

namespace App\Notifications;

use App\Models\Subscription;

/**
 * Notifie le professionnel de l'expiration de son abonnement.
 */
class SubscriptionExpiredNotification extends ApplicationNotification
{
    public function __construct(private readonly Subscription $subscription) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Abonnement expiré',
            'description' => sprintf(
                'Votre abonnement %s a expiré. Vos données sont conservées, mais les nouvelles créations sont limitées jusqu’au renouvellement.',
                $this->subscription->planModel?->name ?? $this->subscription->plan,
            ),
            'tone' => 'danger',
            'icon' => 'alert-triangle',
            'url' => route('pro.subscription.plans'),
            'resource' => ['type' => 'subscription', 'id' => $this->subscription->id],
        ];
    }
}
