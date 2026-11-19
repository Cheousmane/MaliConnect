<?php

namespace App\Notifications;

use App\Models\ProviderBooking;

/**
 * Notifie le client de la confirmation de sa réservation.
 */
class BookingConfirmedNotification extends ApplicationNotification
{
    public function __construct(private readonly ProviderBooking $booking) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Réservation confirmée',
            'description' => sprintf(
                '%s a confirmé votre réservation du %s.',
                $this->booking->providerProfile?->business_name ?? 'Le professionnel',
                $this->formatDate($this->booking->starts_at),
            ),
            'tone' => 'success',
            'icon' => 'calendar-check',
            'url' => route('bookings.index'),
            'resource' => ['type' => 'provider_booking', 'id' => $this->booking->id],
        ];
    }
}
