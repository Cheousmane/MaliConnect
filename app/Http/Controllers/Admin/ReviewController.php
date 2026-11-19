<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\ModerateReviewRequest;
use App\Models\ProviderReview;
use App\Services\Admin\AuditLogger;
use App\Services\Reviews\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Modération administrative des avis clients.
 */
class ReviewController extends Controller
{
    /**
     * Affiche la file de modération des avis.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProviderReview::class);

        $status = (string) $request->string('status');
        $search = trim((string) $request->string('search'));

        $reviews = ProviderReview::query()
            ->with(['customer', 'providerProfile', 'booking'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->whereHas('providerProfile', fn ($profile) => $profile->where('business_name', 'like', '%'.$search.'%'))
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/Reviews', [
            'reviews' => [
                'data' => collect($reviews->items())
                    ->map(fn (ProviderReview $review) => $this->summary($review))
                    ->all(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'statuses' => collect(ReviewStatus::cases())
                ->map(fn (ReviewStatus $status) => ['value' => $status->value, 'label' => $status->label()])
                ->values()
                ->all(),
            'pendingCount' => ProviderReview::pending()->count(),
        ]);
    }

    /**
     * Approuve ou refuse un avis.
     */
    public function moderate(
        ModerateReviewRequest $request,
        ProviderReview $review,
        ReviewService $reviews,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('moderate', $review);

        $previous = $review->status;
        $decision = $request->validated('decision');

        if ($decision === 'approve') {
            $reviews->approve($review);
        } else {
            $reviews->reject($review);
        }

        $audit->log(
            'review.'.($decision === 'approve' ? 'approved' : 'rejected'),
            sprintf(
                'Avis #%d %s par la modération (statut %s → %s).',
                $review->id,
                $decision === 'approve' ? 'publié' : 'refusé',
                $previous->label(),
                $review->status->label(),
            ),
            $review,
            [
                'previous_status' => $previous->value,
                'new_status' => $review->status->value,
                'decision' => $decision,
                'reason' => $request->validated('reason'),
            ],
            $request->user(),
            $request,
        );

        return back()->with('success', 'La décision de modération a été enregistrée.');
    }

    /**
     * Résume un avis pour la file de modération.
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
            'created_at' => $review->created_at?->toIso8601String(),
            'published_at' => $review->published_at?->toIso8601String(),
            'customer' => $review->customer?->name,
            'professional' => $review->providerProfile?->business_name,
            'booking_reference' => $review->booking?->reference,
            'professional_reply' => $review->professional_reply,
        ];
    }
}
