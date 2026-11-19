<?php

use App\Models\Category;
use App\Models\Plan;
use App\Models\PricingSheet;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Subcategory;
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
        ->with(['category', 'subcategory'])
        ->orderByDesc('rating_average')
        ->orderByDesc('rating_count')
        ->limit(3)
        ->get();

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
            'id' => $profile->id,
            'business_name' => $profile->business_name,
            'slug' => $profile->slug,
            'city' => $profile->city,
            'commune' => $profile->commune,
            'category' => $profile->category?->name,
            'subcategory' => $profile->subcategory?->name,
            'rating_average' => (float) $profile->rating_average,
            'rating_count' => $profile->rating_count,
            'is_available' => $profile->is_available,
        ])->values()->all(),
        'stats' => [
            'categories' => $categories->count(),
            'subcategories' => Subcategory::query()->where('is_active', true)->count(),
            'professionals' => ProviderProfile::query()->publiclyVisible()->count(),
        ],
        'plans' => $plans->map(fn (Plan $plan) => [
            'id' => $plan->id,
            'code' => $plan->code,
            'name' => $plan->name,
            'description' => $plan->description,
            'price_monthly' => (float) $plan->price_monthly,
            'price_yearly' => (float) $plan->price_yearly,
            'currency' => $plan->currency,
            'max_services' => $plan->max_services,
            'max_media' => $plan->max_media,
            'max_profiles' => $plan->max_profiles,
            'max_pricing_sheets' => $plan->max_pricing_sheets,
            'formatted_monthly' => $plan->formattedPriceFor('monthly'),
            'formatted_yearly' => $plan->formattedPriceFor('yearly'),
            'is_free' => $plan->isFree(),
            'features' => [
                'advanced_stats' => $plan->has_advanced_stats,
                'gallery' => $plan->has_gallery,
                'collaborators' => $plan->has_collaborators,
                'priority_visibility' => $plan->has_priority_visibility,
                'priority_support' => $plan->has_priority_support,
            ],
        ])->values()->all(),
    ]);
})->name('home');

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

    return Inertia::render('Dashboard', [
        'profiles' => $profiles,
        'stats' => [
            'profiles' => $profileCount,
            'services' => $serviceCount,
            'pricing_sheets' => $pricingSheetCount,
        ],
        'requests' => $recentRequests,
    ]);
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/requests.php';
require __DIR__.'/account.php';
require __DIR__.'/catalog.php';
require __DIR__.'/pro.php';
require __DIR__.'/admin.php';
