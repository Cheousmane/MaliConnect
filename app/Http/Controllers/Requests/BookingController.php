<?php

namespace App\Http\Controllers\Requests;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\ProviderBooking;
use App\Notifications\BookingCancelledNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Réservations du côté client.
 */
class BookingController extends Controller
{
    /**
     * Affiche les réservations du client.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProviderBooking::class);

        $bookings = ProviderBooking::query()
            ->with(['providerProfile', 'service', 'review'])
            ->where('customer_id', $request->user()->id)
            ->orderBy('starts_at')
            ->paginate(12);

        return Inertia::render('requests/Bookings', [
            'bookings' => [
                'data' => collect($bookings->items())->map(fn (ProviderBooking $booking) => [
                    'id' => $booking->id,
                    'reference' => $booking->reference,
                    'starts_at' => $booking->starts_at?->toIso8601String(),
                    'ends_at' => $booking->ends_at?->toIso8601String(),
                    'duration_minutes' => $booking->duration_minutes,
                    'amount' => $booking->amount,
                    'address' => $booking->address,
                    'status' => $booking->status->value,
                    'status_label' => $booking->status->label(),
                    'status_tone' => $booking->status->tone(),
                    'professional' => $booking->providerProfile?->business_name,
                    'professional_slug' => $booking->providerProfile?->slug,
                    'service' => $booking->service?->name,
                    'review' => $booking->review ? [
                        'id' => $booking->review->id,
                        'status' => $booking->review->status->value,
                        'status_label' => $booking->review->status->label(),
                        'status_tone' => $booking->review->status->tone(),
                    ] : null,
                    'can_review' => $request->user()->can('review', $booking),
                ])->values()->all(),
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Annule une réservation.
     */
    public function cancel(Request $request, ProviderBooking $booking, NotificationDispatcher $notifications): RedirectResponse
    {
        // Cette route est réservée au client propriétaire de la réservation.
        if ($booking->customer_id !== $request->user()->id) {
            abort(403);
        }

        $this->authorize('cancel', $booking);

        $booking->forceFill([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => 'customer',
            'cancellation_reason' => $request->string('reason')->toString() ?: null,
        ])->save();

        $booking->load(['customer', 'providerProfile']);

        if ($booking->providerProfile !== null) {
            $notifications->toProfileManagers(
                $booking->providerProfile,
                new BookingCancelledNotification($booking, 'customer'),
            );
        }

        return back()->with('success', 'La réservation a été annulée.');
    }
}
