<?php

namespace App\Notifications;

use App\Models\Payment;

/**
 * Notifie le professionnel que son paiement a été vérifié et accepté.
 */
class PaymentConfirmedNotification extends ApplicationNotification
{
    public function __construct(private readonly Payment $payment) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Paiement confirmé',
            'description' => sprintf(
                'Votre paiement de %s a été vérifié et confirmé. Votre abonnement %s est maintenant actif.',
                $this->payment->formattedAmount(),
                $this->payment->plan?->name ?? '',
            ),
            'tone' => 'success',
            'icon' => 'check-circle',
            'url' => route('pro.subscription.index'),
            'resource' => ['type' => 'payment', 'id' => $this->payment->id],
        ];
    }
}
