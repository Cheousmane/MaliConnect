<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\ProviderBooking;
use App\Models\User;

/**
 * Autorisations sur les réservations.
 */
class ProviderBookingPolicy
{
    /**
     * Autorise la consultation de la liste des réservations.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('bookings.create') || $user->can('bookings.respond');
    }

    /**
     * Autorise la consultation d'une réservation.
     */
    public function view(User $user, ProviderBooking $booking): bool
    {
        return $booking->involves($user) || $user->can('bookings.manage');
    }

    /**
     * Autorise la création d'une réservation par le client.
     */
    public function create(User $user): bool
    {
        return $user->can('bookings.create');
    }

    /**
     * Autorise la confirmation par le professionnel.
     */
    public function confirm(User $user, ProviderBooking $booking): bool
    {
        return $booking->providerProfile?->isManagedBy($user)
            && $user->can('bookings.confirm')
            && $booking->status === BookingStatus::Pending;
    }

    /**
     * Autorise l'annulation par le client ou le professionnel.
     */
    public function cancel(User $user, ProviderBooking $booking): bool
    {
        return $booking->involves($user) && $booking->status->isCancellable();
    }

    /**
     * Autorise la clôture par le professionnel.
     */
    public function complete(User $user, ProviderBooking $booking): bool
    {
        return $booking->providerProfile?->isManagedBy($user)
            && $user->can('bookings.complete')
            && $booking->status === BookingStatus::Confirmed;
    }

    /**
     * Autorise le dépôt d'un avis par le client après la prestation.
     */
    public function review(User $user, ProviderBooking $booking): bool
    {
        return $user->can('reviews.create') && $booking->isReviewableBy($user);
    }
}
