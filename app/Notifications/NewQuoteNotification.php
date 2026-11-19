<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestQuote;

/**
 * Notifie le client de la réception d'un nouveau devis.
 */
class NewQuoteNotification extends ApplicationNotification
{
    public function __construct(
        private readonly ServiceRequest $serviceRequest,
        private readonly ServiceRequestQuote $quote,
    ) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Nouveau devis reçu',
            'description' => sprintf(
                '%s vous a envoyé un devis de %s pour « %s ».',
                $this->serviceRequest->providerProfile?->business_name ?? 'Un professionnel',
                $this->formatAmount($this->quote->amount),
                $this->serviceRequest->title,
            ),
            'tone' => 'success',
            'icon' => 'wallet',
            'url' => route('requests.show', $this->serviceRequest),
            'resource' => ['type' => 'service_request_quote', 'id' => $this->quote->id],
        ];
    }
}
