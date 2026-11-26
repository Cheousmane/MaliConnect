<?php

use App\Http\Controllers\GuidedTourController;
use App\Models\Category;
use App\Models\Plan;
use App\Models\PricingSheet;
use App\Models\ProviderBooking;
use App\Models\ProviderProfile;
use App\Models\ProviderReview;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Subcategory;
use App\Support\Catalog\ProfilePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // Catalogue réel : uniquement les catégories actives du projet.
    $categories = Category::query()
        ->active()
        ->withCount(['subcategories' => fn ($query) => $query->where('is_active', true)])
        ->orderBy('sort_order')
        ->get();

    // Profils publiquement visibles : vérifiés et actifs.
    $profiles = ProviderProfile::query()
        ->publiclyVisible()
        ->with([
            'category',
            'subcategory',
            'services' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
        ])
        ->withCount([
            'services' => fn ($query) => $query->where('is_active', true),
            'media',
        ])
        ->orderByDesc('rating_average')
        ->orderByDesc('rating_count')
        ->orderByDesc('verified_at')
        ->limit(3)
        ->get();

    // Communes réellement renseignées par les professionnels publiés.
    $locations = ProviderProfile::query()
        ->publiclyVisible()
        ->whereNotNull('commune')
        ->where('commune', '!=', '')
        ->distinct()
        ->orderBy('commune')
        ->limit(20)
        ->pluck('commune')
        ->values()
        ->all();

    // Formules commerciales actives, triées par ordre commercial.
    $plans = Plan::query()
        ->active()
        ->ordered()
        ->get();

    return Inertia::render('Welcome', [
        'categories' => $categories->map(fn (Category $category) => [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'icon' => $category->icon,
            'color' => $category->color,
            'subcategories_count' => $category->subcategories_count,
        ])->values()->all(),
        'profiles' => $profiles->map(fn (ProviderProfile $profile) => [
            ...ProfilePresenter::summary($profile),
            'years_experience' => $profile->years_experience,
            'latitude' => $profile->latitude,
            'longitude' => $profile->longitude,
            'services_count' => $profile->services_count,
            'media_count' => $profile->media_count,
            'services' => $profile->services
                ->take(2)
                ->map(fn (Service $service) => [
                    'name' => $service->name,
                    'formatted_price' => $service->formatted_price,
                    'price_type' => $service->price_type,
                ])
                ->values()
                ->all(),
        ])->values()->all(),
        'stats' => [
            'categories' => $categories->count(),
            'subcategories' => Subcategory::query()->where('is_active', true)->count(),
            'professionals' => ProviderProfile::query()->publiclyVisible()->count(),
            'services' => Service::query()
                ->where('is_active', true)
                ->whereHas('providerProfile', fn ($query) => $query->publiclyVisible())
                ->count(),
        ],
        'locations' => $locations,
        // Exemple tarifaire réel : première prestation publiée du catalogue.
        'priceExample' => (function () use ($profiles) {
            $profile = $profiles->first();
            $service = $profile?->services->first();

            if (! $profile || ! $service) {
                return null;
            }

            return [
                'service_name' => $service->name,
                'professional_name' => $profile->business_name,
                'professional_slug' => $profile->slug,
                'formatted_price' => $service->formatted_price,
            ];
        })(),
        'plans' => $plans->map(fn (Plan $plan) => [
            'id' => $plan->id,
            'code' => $plan->code,
            'name' => $plan->name,
            'description' => $plan->description,
            'price_monthly' => (float) $plan->price_monthly,
            'price_yearly' => (float) $plan->price_yearly,
            'currency' => $plan->currency,
            'amount_monthly' => $plan->formattedPriceFor('monthly'),
            'amount_yearly' => $plan->formattedPriceFor('yearly'),
            'max_services' => $plan->max_services,
            'max_media' => $plan->max_media,
            'max_profiles' => $plan->max_profiles,
            'max_pricing_sheets' => $plan->max_pricing_sheets,
            'is_free' => $plan->isFree(),
            'trial_days' => $plan->trial_days,
            'features' => [
                'advanced_stats' => (bool) $plan->has_advanced_stats,
                'gallery' => (bool) $plan->has_gallery,
                'collaborators' => (bool) $plan->has_collaborators,
                'priority_visibility' => (bool) $plan->has_priority_visibility,
                'priority_support' => (bool) $plan->has_priority_support,
                'advanced_quotes' => (bool) $plan->has_advanced_quotes,
                'bookings' => (bool) $plan->has_bookings,
                'simple_pricing_sheets' => (bool) $plan->has_simple_pricing_sheets,
                'full_pricing_sheets' => (bool) $plan->has_full_pricing_sheets,
                'profile_badge' => (bool) $plan->has_profile_badge,
                'highlight_options' => (bool) $plan->has_highlight_options,
            ],
        ])->values()->all(),
    ]);
})->name('home');

Route::get('/politique-de-confidentialite', fn () => Inertia::render('PrivacyPolicy'))->name('privacy');

Route::get('dashboard', function (Request $request) {
    $user = $request->user();

    // Profils professionnels réellement rattachés au compte connecté.
    $profiles = ProviderProfile::query()
        ->where('user_id', $user->id)
        ->with('category')
        ->latest()
        ->limit(3)
        ->get()
        ->map(fn (ProviderProfile $profile) => [
            'id' => $profile->id,
            'business_name' => $profile->business_name,
            'status' => $profile->status->value,
            'status_label' => $profile->status->label(),
            'status_tone' => $profile->status->tone(),
            'is_active' => $profile->is_active,
            'category' => $profile->category?->name,
            'city' => $profile->city,
        ])
        ->values()
        ->all();

    // Statistiques réelles pour ce compte.
    $profileCount = ProviderProfile::query()->where('user_id', $user->id)->count();
    $serviceCount = Service::query()
        ->whereHas('providerProfile', fn ($q) => $q->where('user_id', $user->id))
        ->count();
    $pricingSheetCount = PricingSheet::query()
        ->whereHas('providerProfile', fn ($q) => $q->where('user_id', $user->id))
        ->count();

    // Complétude du profil professionnel principal (dynamique).
    $primaryProfile = ProviderProfile::query()
        ->where('user_id', $user->id)
        ->latest()
        ->first();

    $profileCompleteness = null;
    if ($primaryProfile) {
        $hasPhoto = filled($primaryProfile->logo_path);
        $hasDescription = filled($primaryProfile->description) && mb_strlen(trim((string) $primaryProfile->description)) >= 20;
        $hasArea = filled($primaryProfile->city) && (
            filled($primaryProfile->address)
            || filled($primaryProfile->commune)
            || filled($primaryProfile->quartier)
            || ($primaryProfile->latitude !== null && $primaryProfile->longitude !== null)
        );
        $isVerified = $primaryProfile->status === \App\Enums\ProfileStatus::Verified;

        $items = [
            ['label' => 'Photo de profil', 'complete' => $hasPhoto],
            ['label' => 'Présentation professionnelle', 'complete' => $hasDescription],
            ['label' => 'Zone d’intervention', 'complete' => $hasArea],
            ['label' => 'Documents vérifiés', 'complete' => $isVerified],
        ];

        $completeCount = collect($items)->where('complete', true)->count();
        $profileCompleteness = [
            'profile_id' => $primaryProfile->id,
            'percentage' => (int) round(($completeCount / count($items)) * 100),
            'items' => $items,
        ];
    }

    // Demandes récentes reçues sur les profils de l'utilisateur.
    $recentRequests = ServiceRequest::query()
        ->whereHas('providerProfile', fn ($q) => $q->where('user_id', $user->id))
        ->with(['customer:id,name', 'service:id,name', 'providerProfile:id,business_name'])
        ->latest()
        ->limit(5)
        ->get()
        ->map(fn (ServiceRequest $request) => [
            'id' => $request->id,
            'client' => $request->customer?->name ?? 'Client inconnu',
            'initials' => strtoupper(substr($request->customer?->name ?? 'CI', 0, 2)),
            'city' => $request->city ?? '—',
            'service' => $request->service?->name ?? $request->title ?? 'Service',
            'date' => $request->created_at?->diffForHumans() ?? '—',
            'statusTone' => $request->status->tone(),
            'statusLabel' => $request->status->label(),
            'amount' => $request->budget_max ? number_format((float) $request->budget_max, 0, ',', ' ').' FCFA' : '—',
        ])
        ->values()
        ->all();

    // Activité récente réelle du compte (événements pro + client).
    $activities = collect();

    $recentReceivedRequests = ServiceRequest::query()
        ->whereHas('providerProfile', fn ($q) => $q->where('user_id', $user->id))
        ->with(['customer:id,name', 'service:id,name'])
        ->latest()
        ->limit(5)
        ->get();

    foreach ($recentReceivedRequests as $request) {
        $customerName = $request->customer?->name ?? 'Un client';
        $serviceName = $request->service?->name ?? $request->title ?? 'Service';
        $activities->push([
            'id' => 'request-'.$request->id,
            'icon' => 'inbox',
            'tone' => 'brand',
            'title' => 'Nouvelle demande de '.$customerName,
            'description' => $serviceName,
            'created_at' => $request->created_at?->toIso8601String(),
        ]);
    }

    $recentBookings = ProviderBooking::query()
        ->whereHas('providerProfile', fn ($q) => $q->where('user_id', $user->id))
        ->with(['customer:id,name', 'service:id,name'])
        ->latest()
        ->limit(5)
        ->get();

    foreach ($recentBookings as $booking) {
        $customerName = $booking->customer?->name ?? 'Un client';
        $serviceName = $booking->service?->name ?? 'Prestation';
        $activities->push([
            'id' => 'booking-'.$booking->id,
            'icon' => 'calendar',
            'tone' => 'info',
            'title' => 'Réservation de '.$customerName,
            'description' => $serviceName,
            'created_at' => $booking->created_at?->toIso8601String(),
        ]);
    }

    $recentReviews = ProviderReview::query()
        ->whereHas('providerProfile', fn ($q) => $q->where('user_id', $user->id))
        ->with(['customer:id,name'])
        ->latest()
        ->limit(5)
        ->get();

    foreach ($recentReviews as $review) {
        $customerName = $review->customer?->name ?? 'Un client';
        $activities->push([
            'id' => 'review-'.$review->id,
            'icon' => 'star',
            'tone' => 'warning',
            'title' => 'Nouvel avis de '.$customerName,
            'description' => $review->rating.'/5'.($review->comment ? ' · '.mb_strimwidth((string) $review->comment, 0, 60, '…') : ''),
            'created_at' => $review->created_at?->toIso8601String(),
        ]);
    }

    // Côté client : demandes et réservations effectuées par l'utilisateur.
    $myRequests = ServiceRequest::query()
        ->where('customer_id', $user->id)
        ->with(['providerProfile:id,business_name', 'service:id,name'])
        ->latest()
        ->limit(5)
        ->get();

    foreach ($myRequests as $request) {
        $providerName = $request->providerProfile?->business_name ?? 'Un professionnel';
        $serviceName = $request->service?->name ?? $request->title ?? 'Service';
        $activities->push([
            'id' => 'my-request-'.$request->id,
            'icon' => 'send',
            'tone' => 'brand',
            'title' => 'Demande envoyée à '.$providerName,
            'description' => $serviceName,
            'created_at' => $request->created_at?->toIso8601String(),
        ]);
    }

    $myBookings = ProviderBooking::query()
        ->where('customer_id', $user->id)
        ->with(['providerProfile:id,business_name', 'service:id,name'])
        ->latest()
        ->limit(5)
        ->get();

    foreach ($myBookings as $booking) {
        $providerName = $booking->providerProfile?->business_name ?? 'Un professionnel';
        $serviceName = $booking->service?->name ?? 'Prestation';
        $activities->push([
            'id' => 'my-booking-'.$booking->id,
            'icon' => 'calendar',
            'tone' => 'info',
            'title' => 'Réservation chez '.$providerName,
            'description' => $serviceName,
            'created_at' => $booking->created_at?->toIso8601String(),
        ]);
    }

    $activities = $activities
        ->filter(fn (array $activity) => filled($activity['created_at']))
        ->sortByDesc('created_at')
        ->take(5)
        ->values()
        ->all();

    return Inertia::render('Dashboard', [
        'profiles' => $profiles,
        'stats' => [
            'profiles' => $profileCount,
            'services' => $serviceCount,
            'pricing_sheets' => $pricingSheetCount,
        ],
        'requests' => $recentRequests,
        'profileCompleteness' => $profileCompleteness,
        'activities' => $activities,
    ]);
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

Route::middleware(['auth', 'verified', 'active', 'throttle:60,1'])->prefix('tour')->name('tour.')->group(function () {
    Route::get('/', [GuidedTourController::class, 'show'])->name('show');
    Route::patch('/', [GuidedTourController::class, 'update'])->name('update');
    Route::post('/restart', [GuidedTourController::class, 'restart'])->name('restart');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/requests.php';
require __DIR__.'/account.php';
require __DIR__.'/catalog.php';
require __DIR__.'/pro.php';
require __DIR__.'/admin.php';
