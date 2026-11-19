<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBookingStatusRequest;
use App\Models\ProviderBooking;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Supervision administrative des réservations.
 */
class BookingController extends Controller
{
    /**
     * Affiche la liste filtrable des réservations.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('bookings.manage'), 403);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status');

        $paginator = ProviderBooking::query()
            ->with(['customer:id,name', 'providerProfile:id,business_name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('reference', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('providerProfile', fn ($profile) => $profile->where('business_name', 'like', '%'.$search.'%'));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/Bookings', [
            'bookings' => [
                'data' => collect($paginator->items())->map(fn (ProviderBooking $booking) => $this->summarize($booking))->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => $this->statuses(),
            'pendingCount' => ProviderBooking::query()->where('status', BookingStatus::Pending)->count(),
        ]);
    }

    /**
     * Affiche le détail d'une réservation.
     */
    public function show(Request $request, ProviderBooking $booking): Response
    {
        abort_unless($request->user()->can('bookings.manage'), 403);

        $booking->load([
            'customer:id,name,email,phone',
            'providerProfile:id,business_name,slug',
            'service:id,name',
            'serviceRequest:id,reference,title',
            'review:id,provider_booking_id,rating,comment,status',
        ]);

        return Inertia::render('admin/BookingDetail', [
            'booking' => [
                ...$this->summarize($booking),
                'address' => $booking->address,
                'notes' => $booking->notes,
                'duration_minutes' => $booking->duration_minutes,
                'starts_at' => $booking->starts_at?->toIso8601String(),
                'ends_at' => $booking->ends_at?->toIso8601String(),
                'confirmed_at' => $booking->confirmed_at?->toIso8601String(),
                'completed_at' => $booking->completed_at?->toIso8601String(),
                'cancelled_at' => $booking->cancelled_at?->toIso8601String(),
                'cancelled_by' => $booking->cancelled_by,
                'cancellation_reason' => $booking->cancellation_reason,
                'customer' => $booking->customer?->only(['id', 'name', 'email', 'phone']),
                'professional' => $booking->providerProfile?->only(['id', 'business_name', 'slug']),
                'service' => $booking->service?->name,
                'service_request' => $booking->serviceRequest?->only(['id', 'reference', 'title']),
                'review' => $booking->review?->only(['id', 'rating', 'comment', 'status']),
            ],
            'statuses' => $this->statuses(),
        ]);
    }

    /**
     * Change le statut administratif d'une réservation.
     */
    public function updateStatus(
        UpdateBookingStatusRequest $request,
        ProviderBooking $booking,
        AuditLogger $audit,
    ): RedirectResponse {
        abort_unless($request->user()->can('bookings.manage'), 403);

        $status = $request->enum('status', BookingStatus::class);
        $previous = $booking->status;

        DB::transaction(function () use ($request, $booking, $status, $previous, $audit) {
            $attributes = ['status' => $status];

            if ($status === BookingStatus::Confirmed && $booking->confirmed_at === null) {
                $attributes['confirmed_at'] = now();
            }

            if ($status === BookingStatus::Completed && $booking->completed_at === null) {
                $attributes['completed_at'] = now();
            }

            if ($status === BookingStatus::Cancelled) {
                $attributes['cancelled_at'] = $booking->cancelled_at ?? now();
                $attributes['cancelled_by'] = 'admin';
                $attributes['cancellation_reason'] = $request->validated('note');
            }

            $booking->forceFill($attributes)->save();

            $audit->log(
                'booking.status.updated',
                sprintf(
                    'Réservation %s passée de « %s » à « %s » par l’administration.',
                    $booking->reference,
                    $previous->label(),
                    $status->label(),
                ),
                $booking,
                [
                    'previous_status' => $previous->value,
                    'new_status' => $status->value,
                    'note' => $request->validated('note'),
                ],
                $request->user(),
                $request,
            );
        });

        return back()->with('success', 'Le statut de la réservation a été mis à jour.');
    }

    /**
     * Résume une réservation pour l'interface d'administration.
     *
     * @return array<string, mixed>
     */
    private function summarize(ProviderBooking $booking): array
    {
        return [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'status_tone' => $booking->status->tone(),
            'customer' => $booking->customer?->name,
            'professional' => $booking->providerProfile?->business_name,
            'starts_at' => $booking->starts_at?->toIso8601String(),
            'amount' => (float) $booking->amount,
            'created_at' => $booking->created_at?->toIso8601String(),
        ];
    }

    /**
     * Liste des statuts disponibles.
     *
     * @return list<array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return collect(BookingStatus::cases())
            ->map(fn (BookingStatus $status) => ['value' => $status->value, 'label' => $status->label()])
            ->values()
            ->all();
    }
}
