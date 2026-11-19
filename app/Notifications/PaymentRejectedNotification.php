<?php

namespace App\Notifications;

use App\Models\Payment;

/**
 * Notifie le professionnel du refus d'un paiement et de son motif.
 */
class PaymentRejectedNotification extends ApplicationNotification
{
    public function __construct(private readonly Payment $payment) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Paiement refusé',
            'description' => sprintf(
                'Votre paiement de %s n’a pas pu être validé. Motif : %s. Vous pouvez déposer une nouvelle preuve depuis votre espace abonnement.',
                $this->payment->formattedAmount(),
                $this->payment->rejection_reason ?? 'justification non précisée',
            ),
            'tone' => 'danger',
            'icon' => 'x-circle',
            'url' => route('pro.subscription.index'),
            'resource' => ['type' => 'payment', 'id' => $this->payment->id],
        ];
    }
}
