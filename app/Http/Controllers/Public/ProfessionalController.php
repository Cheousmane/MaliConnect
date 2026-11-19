<?php

namespace App\Http\Controllers\Public;

use App\Enums\ReportReason;
use App\Http\Controllers\Controller;
use App\Models\PricingSheet;
use App\Models\ProviderProfile;
use App\Support\Catalog\ProfilePresenter;
use App\Support\Pricing\PricingPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Fiche publique d'un professionnel.
 */
class ProfessionalController extends Controller
{
    /**
     * Affiche le profil public d'un professionnel vérifié.
     */
    public function show(Request $request, ProviderProfile $profile): Response
    {
        // Seuls les profils vérifiés et actifs sont présentés publiquement.
        abort_unless($profile->isPubliclyVisible(), 404);

        $profile->load([
            'category',
            'subcategory',
            'business',
            'services' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            'media',
            'hours',
            'serviceAreas',
            'pricingSheets' => fn ($query) => $query
                ->publiclyVisible()
                ->with([
                    'providerProfile',
                    'category',
                    'items' => fn ($items) => $items->where('is_available', true),
                    'items.service',
                    'items.category',
                ])
                ->latest('published_at'),
            'approvedReviews' => fn ($query) => $query->with('customer:id,name')->latest('published_at')->limit(10),
        ]);

        return Inertia::render('public/Professional', [
            'profile' => ProfilePresenter::detail($profile),
            'pricingSheets' => $profile->pricingSheets
                ->map(fn (PricingSheet $sheet) => PricingPresenter::publicSheet($sheet))
                ->values()
                ->all(),
            'reviews' => $profile->approvedReviews->map(fn ($review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'customer' => $review->customer?->name,
                'published_at' => $review->published_at?->toIso8601String(),
                'professional_reply' => $review->professional_reply,
                'replied_at' => $review->replied_at?->toIso8601String(),
            ])->values()->all(),
            'isFavorited' => $request->user() !== null
                && $request->user()->favorites()->where('provider_profile_id', $profile->id)->exists(),
            'canFavorite' => $request->user() !== null,
            'canReport' => $request->user()?->can('reports.create') ?? false,
            'hasOpenReport' => $request->user() !== null
                && $request->user()->reports()
                    ->where('reportable_type', $profile->getMorphClass())
                    ->where('reportable_id', $profile->id)
                    ->open()
                    ->exists(),
            'reportReasons' => collect(ReportReason::cases())
                ->map(fn (ReportReason $reason) => ['value' => $reason->value, 'label' => $reason->label()])
                ->values()
                ->all(),
        ]);
    }
}
