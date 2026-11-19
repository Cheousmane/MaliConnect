<?php

namespace App\Notifications;

use App\Models\Plan;

/**
 * Informe le professionnel qu'une limite de sa formule est atteinte.
 */
class PlanLimitReachedNotification extends ApplicationNotification
{
    /**
     * Nom de la formule, conservé sous forme scalaire pour rester sérialisable
     * même lorsque le plan provient du secours non persisté (base non seedée).
     */
    private readonly string $planName;

    public function __construct(
        Plan $plan,
        private readonly string $limitLabel,
    ) {
        $this->planName = $plan->name;
    }

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Limite de formule atteinte',
            'description' => sprintf(
                'Vous avez atteint la limite de %s de votre formule %s. Passez à une formule supérieure pour continuer à créer.',
                $this->limitLabel,
                $this->planName,
            ),
            'tone' => 'warning',
            'icon' => 'trending-up',
            'url' => route('pro.subscription.plans'),
        ];
    }
}
