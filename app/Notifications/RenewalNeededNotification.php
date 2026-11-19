<?php

namespace App\Notifications;

use App\Models\Subscription;

/**
 * Signale qu'un renouvellement est nécessaire pendant la période de grâce.
 */
class RenewalNeededNotification extends ApplicationNotification
{
    public function __construct(private readonly Subscription $subscription) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Renouvellement nécessaire',
            'description' => sprintf(
                'Votre abonnement %s est arrivé à échéance. Une période de grâce est ouverte jusqu’au %s : renouvelez avant cette date pour éviter toute interruption.',
                $this->subscription->planModel?->name ?? $this->subscription->plan,
                $this->formatDate($this->subscription->grace_ends_at),
            ),
            'tone' => 'warning',
            'icon' => 'refresh-cw',
            'url' => route('pro.subscription.plans'),
            'resource' => ['type' => 'subscription', 'id' => $this->subscription->id],
        ];
    }
}
