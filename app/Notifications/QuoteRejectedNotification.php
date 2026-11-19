<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestQuote;

/**
 * Notifie le professionnel lorsque son devis est refusé par le client.
 */
class QuoteRejectedNotification extends ApplicationNotification
{
    public function __construct(
        private readonly ServiceRequest $serviceRequest,
        private readonly ServiceRequestQuote $quote,
        private readonly ?string $reason = null,
    ) {}

    protected function payload(object $notifiable): array
    {
        $description = sprintf(
            '%s a refusé votre devis de %s pour « %s ».',
            $this->serviceRequest->customer?->name ?? 'Le client',
            $this->formatAmount($this->quote->amount),
            $this->serviceRequest->title,
        );

        if ($this->reason !== null && $this->reason !== '') {
            $description .= ' Motif : '.$this->reason;
        }

        return [
            'title' => 'Devis refusé',
            'description' => $description,
            'tone' => 'danger',
            'icon' => 'x-circle',
            'url' => route('pro.requests.show', $this->serviceRequest),
            'resource' => ['type' => 'service_request_quote', 'id' => $this->quote->id],
        ];
    }
}
