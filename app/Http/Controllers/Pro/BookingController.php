<?php

namespace App\Http\Controllers\Pro;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\ProviderBooking;
use App\Models\ProviderProfile;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingConfirmedNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Réservations reçues par un professionnel.
 */
class BookingController extends Controller
{
    /**
     * Transitions de statut autorisées pour le professionnel.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'confirmed' => ['pending'],
        'completed' => ['confirmed'],
        'cancelled' => ['pending', 'confirmed'],
    ];

    /**
     * Affiche les réservations du professionnel.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProviderBooking::class);

        $profileIds = ProviderProfile::query()
            ->managedBy($request->user())
            ->pluck('id');

        $status = (string) $request->string('status');

        $bookings = ProviderBooking::query()
            ->with(['customer', 'service'])
            ->whereIn('provider_profile_id', $profileIds)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('starts_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('pro/Bookings', [
            'bookings' => [
                'data' => collect($bookings->items())->map(fn (ProviderBooking $booking) => $this->summary($booking))->all(),
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
            ],
            'filters' => ['status' => $status],
            'statuses' => collect(BookingStatus::cases())
                ->map(fn (BookingStatus $status) => ['value' => $status->value, 'label' => $status->label()])
                ->values()
                ->all(),
            'transitions' => self::TRANSITIONS,
        ]);
    }

    /**
     * Change le statut d'une réservation.
     */
    public function updateStatus(Request $request, ProviderBooking $booking, NotificationDispatcher $notifications): RedirectResponse
    {
        $this->authorize('view', $booking);

        // Seul un gestionnaire du profil destinataire peut piloter la réservation.
        if (! $booking->providerProfile?->isManagedBy($request->user())) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $status = BookingStatus::tryFrom($validated['status']);

        if ($status === null) {
            throw ValidationException::withMessages(['status' => 'Statut inconnu.']);
        }

        $allowed = self::TRANSITIONS[$status->value] ?? [];

        if (! in_array($booking->status->value, $allowed, true)) {
            return back()->with('error', 'Ce changement de statut n’est pas autorisé.');
        }

        // Chaque transition est contrôlée par la policy correspondante.
        $this->authorize(match ($status) {
            BookingStatus::Confirmed => 'confirm',
            BookingStatus::Completed => 'complete',
            BookingStatus::Cancelled => 'cancel',
        }, $booking);

        $booking->forceFill([
            'status' => $status,
            'confirmed_at' => $status === BookingStatus::Confirmed ? now() : $booking->confirmed_at,
            'completed_at' => $status === BookingStatus::Completed ? now() : $booking->completed_at,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now() : $booking->cancelled_at,
            'cancelled_by' => $status === BookingStatus::Cancelled ? 'professional' : $booking->cancelled_by,
            'cancellation_reason' => $status === BookingStatus::Cancelled ? ($validated['reason'] ?? null) : $booking->cancellation_reason,
        ])->save();

        $booking->load(['customer', 'providerProfile']);

        if ($booking->customer !== null) {
            if ($status === BookingStatus::Confirmed) {
                $notifications->toUser($booking->customer, new BookingConfirmedNotification($booking));
            }

            if ($status === BookingStatus::Cancelled) {
                $notifications->toUser($booking->customer, new BookingCancelledNotification($booking, 'professional'));
            }
        }

        return back()->with('success', 'Le statut de la réservation a été mis à jour.');
    }

    /**
     * Résume une réservation pour les listes.
     *
     * @return array<string, mixed>
     */
    private function summary(ProviderBooking $booking): array
    {
        return [
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
            'customer' => $booking->customer?->name,
            'customer_phone' => $booking->customer?->phone,
            'service' => $booking->service?->name,
            'created_at' => $booking->created_at?->toIso8601String(),
        ];
    }
}
