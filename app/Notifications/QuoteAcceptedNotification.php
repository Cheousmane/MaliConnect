<?php

namespace App\Notifications;

use App\Models\ProviderBooking;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestQuote;

/**
 * Notifie le professionnel lorsque son devis est accepté.
 */
class QuoteAcceptedNotification extends ApplicationNotification
{
    public function __construct(
        private readonly ServiceRequest $serviceRequest,
        private readonly ServiceRequestQuote $quote,
        private readonly ProviderBooking $booking,
    ) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Devis accepté',
            'description' => sprintf(
                '%s a accepté votre devis de %s. La réservation %s a été créée.',
                $this->serviceRequest->customer?->name ?? 'Le client',
                $this->formatAmount($this->quote->amount),
                $this->booking->reference,
            ),
            'tone' => 'success',
            'icon' => 'check-circle',
            'url' => route('pro.requests.show', $this->serviceRequest),
            'resource' => ['type' => 'provider_booking', 'id' => $this->booking->id],
        ];
    }
}
