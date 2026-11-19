<?php

namespace App\Notifications;

use App\Models\ProviderBooking;
use App\Models\User;

/**
 * Notifie la partie adverse de l'annulation d'une réservation.
 */
class BookingCancelledNotification extends ApplicationNotification
{
    public function __construct(
        private readonly ProviderBooking $booking,
        private readonly string $cancelledBy,
    ) {}

    protected function payload(object $notifiable): array
    {
        $isCustomer = $notifiable instanceof User && $notifiable->id === $this->booking->customer_id;
        $actor = $this->cancelledBy === 'professional' ? 'le professionnel' : 'le client';

        return [
            'title' => 'Réservation annulée',
            'description' => sprintf(
                'La réservation du %s a été annulée par %s.',
                $this->formatDate($this->booking->starts_at),
                $actor,
            ),
            'tone' => 'danger',
            'icon' => 'calendar-x',
            'url' => $isCustomer ? route('bookings.index') : route('pro.bookings.index'),
            'resource' => ['type' => 'provider_booking', 'id' => $this->booking->id],
        ];
    }
}
