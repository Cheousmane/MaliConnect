<?php

namespace App\Notifications;

use App\Models\ProviderBooking;

/**
 * Notifie les gestionnaires d'un profil d'une nouvelle réservation.
 */
class NewBookingNotification extends ApplicationNotification
{
    public function __construct(private readonly ProviderBooking $booking) {}

    protected function payload(object $notifiable): array
    {
        return [
            'title' => 'Nouvelle réservation',
            'description' => sprintf(
                '%s a réservé « %s » le %s.',
                $this->booking->customer?->name ?? 'Un client',
                $this->booking->service?->name ?? 'une prestation',
                $this->formatDate($this->booking->starts_at),
            ),
            'tone' => 'brand',
            'icon' => 'calendar-plus',
            'url' => route('pro.bookings.index'),
            'resource' => ['type' => 'provider_booking', 'id' => $this->booking->id],
        ];
    }
}
