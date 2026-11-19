<?php

namespace App\Notifications;

use App\Models\Payment;

/**
 * Notifie le professionnel et les administrateurs qu'un paiement a été déclaré.
 */
class PaymentReceivedNotification extends ApplicationNotification
{
    public function __construct(
        private readonly Payment $payment,
        private readonly bool $forAdmin = false,
    ) {}

    protected function payload(object $notifiable): array
    {
        if ($this->forAdmin) {
            return [
                'title' => 'Nouveau paiement à vérifier',
                'description' => sprintf(
                    '%s a déclaré un paiement de %s pour la formule %s. Vérifiez la preuve avant validation.',
                    $this->payment->ownerLabel(),
                    $this->payment->formattedAmount(),
                    $this->payment->plan?->name ?? 'inconnue',
                ),
                'tone' => 'warning',
                'icon' => 'wallet',
                'url' => route('admin.payments.show', $this->payment),
                'resource' => ['type' => 'payment', 'id' => $this->payment->id],
            ];
        }

        return [
            'title' => 'Paiement reçu',
            'description' => sprintf(
                'Votre déclaration de paiement de %s a bien été reçue. Elle sera vérifiée par notre équipe avant activation.',
                $this->payment->formattedAmount(),
            ),
            'tone' => 'info',
            'icon' => 'wallet',
            'url' => route('pro.subscription.index'),
            'resource' => ['type' => 'payment', 'id' => $this->payment->id],
        ];
    }
}
