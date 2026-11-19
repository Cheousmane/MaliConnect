<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\StoreReviewRequest;
use App\Models\ProviderBooking;
use App\Models\ProviderReview;
use App\Notifications\NewReviewNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Reviews\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Avis déposés par le client sur ses prestations terminées.
 */
class ReviewController extends Controller
{
    /**
     * Affiche l'historique des avis du client.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProviderReview::class);

        $reviews = ProviderReview::query()
            ->with(['providerProfile', 'booking'])
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return Inertia::render('requests/Reviews', [
            'reviews' => [
                'data' => collect($reviews->items())->map(fn (ProviderReview $review) => $this->summary($review))->all(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Affiche le formulaire d'avis d'une réservation terminée.
     */
    public function create(Request $request, ProviderBooking $booking): Response
    {
        $this->authorize('review', $booking);

        $booking->load(['providerProfile', 'service']);

        return Inertia::render('requests/ReviewCreate', [
            'booking' => [
                'id' => $booking->id,
                'reference' => $booking->reference,
                'starts_at' => $booking->starts_at?->toIso8601String(),
                'amount' => $booking->amount,
                'professional' => $booking->providerProfile?->business_name,
                'professional_slug' => $booking->providerProfile?->slug,
                'service' => $booking->service?->name,
            ],
        ]);
    }

    /**
     * Enregistre l'avis et notifie les gestionnaires du profil.
     */
    public function store(
        StoreReviewRequest $request,
        ProviderBooking $booking,
        ReviewService $reviews,
        NotificationDispatcher $notifications,
    ): RedirectResponse {
        $this->authorize('review', $booking);

        $review = $reviews->create($booking, $request->user(), $request->validated());
        $review->load('customer');

        $notifications->toProfileManagers(
            $review->providerProfile,
            new NewReviewNotification($review),
        );

        return to_route('reviews.index')
            ->with('success', 'Merci ! Votre avis a été envoyé pour modération.');
    }

    /**
     * Résume un avis pour la liste du client.
     *
     * @return array<string, mixed>
     */
    private function summary(ProviderReview $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'status' => $review->status->value,
            'status_label' => $review->status->label(),
            'status_tone' => $review->status->tone(),
            'published_at' => $review->published_at?->toIso8601String(),
            'created_at' => $review->created_at?->toIso8601String(),
            'professional' => $review->providerProfile?->business_name,
            'professional_slug' => $review->providerProfile?->slug,
            'booking_reference' => $review->booking?->reference,
            'professional_reply' => $review->professional_reply,
            'replied_at' => $review->replied_at?->toIso8601String(),
        ];
    }
}
