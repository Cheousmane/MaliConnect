<?php

namespace App\Http\Controllers\Pro;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\RespondReviewRequest;
use App\Models\ProviderProfile;
use App\Models\ProviderReview;
use App\Services\Reviews\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Avis reçus par les profils professionnels du compte.
 */
class ReviewController extends Controller
{
    /**
     * Affiche les avis reçus et les statistiques de satisfaction.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProviderReview::class);

        $profileIds = ProviderProfile::query()
            ->managedBy($request->user())
            ->pluck('id');

        $status = (string) $request->string('status');

        $reviews = ProviderReview::query()
            ->with(['customer', 'providerProfile', 'booking'])
            ->whereIn('provider_profile_id', $profileIds)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = ProviderReview::query()
            ->whereIn('provider_profile_id', $profileIds)
            ->where('status', ReviewStatus::Approved)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        return Inertia::render('pro/Reviews', [
            'reviews' => [
                'data' => collect($reviews->items())
                    ->map(fn (ProviderReview $review) => $this->summary($review, $request))
                    ->all(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
            'filters' => ['status' => $status],
            'statuses' => collect(ReviewStatus::cases())
                ->map(fn (ReviewStatus $status) => ['value' => $status->value, 'label' => $status->label()])
                ->values()
                ->all(),
            'stats' => [
                'average' => round((float) ($stats->average ?? 0), 1),
                'count' => (int) ($stats->total ?? 0),
                'pending' => ProviderReview::query()
                    ->whereIn('provider_profile_id', $profileIds)
                    ->where('status', ReviewStatus::Pending)
                    ->count(),
            ],
        ]);
    }

    /**
     * Publie la réponse du professionnel à un avis approuvé.
     */
    public function respond(RespondReviewRequest $request, ProviderReview $review, ReviewService $reviews): RedirectResponse
    {
        $this->authorize('respond', $review);

        $reviews->reply($review, $request->validated('reply'));

        return back()->with('success', 'Votre réponse a été publiée.');
    }

    /**
     * Résume un avis pour la liste professionnelle.
     *
     * @return array<string, mixed>
     */
    private function summary(ProviderReview $review, Request $request): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'status' => $review->status->value,
            'status_label' => $review->status->label(),
            'status_tone' => $review->status->tone(),
            'created_at' => $review->created_at?->toIso8601String(),
            'published_at' => $review->published_at?->toIso8601String(),
            'customer' => $review->customer?->name,
            'professional' => $review->providerProfile?->business_name,
            'booking_reference' => $review->booking?->reference,
            'professional_reply' => $review->professional_reply,
            'replied_at' => $review->replied_at?->toIso8601String(),
            'can_respond' => $request->user()->can('respond', $review),
        ];
    }
}
