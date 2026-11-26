<?php

namespace App\Http\Controllers\Pro;

use App\Enums\BusinessStatus;
use App\Enums\MediaType;
use App\Enums\ProfileStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pro\StoreProviderProfileRequest;
use App\Http\Requests\Pro\StoreServiceRequest;
use App\Http\Requests\Pro\UpdateProviderProfileRequest;
use App\Http\Requests\Pro\UpdateServiceRequest;
use App\Models\Business;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\ProviderProfileHour;
use App\Models\ServiceArea;
use App\Models\User;
use App\Rules\MaliPhoneNumber;
use App\Services\Monetization\EntitlementService;
use App\Services\Monetization\SubscriptionNotifier;
use App\Services\Monetization\SubscriptionService;
use App\Support\Identity\SlugGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des profils professionnels dans l'espace prestataire.
 */
class ProviderProfileController extends Controller
{
    /**
     * Affiche la liste des profils pilotés par l'utilisateur.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProviderProfile::class);

        $user = $request->user();

        $status = (string) $request->string('status');

        $profiles = $this->manageableProfilesQuery($user)
            ->with(['category', 'business'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('pro/Profiles', [
            'profiles' => [
                'data' => collect($profiles->items())->map(fn (ProviderProfile $profile) => $this->summarize($profile))->all(),
                'current_page' => $profiles->currentPage(),
                'last_page' => $profiles->lastPage(),
                'total' => $profiles->total(),
            ],
            'statuses' => $this->statuses(),
            'filters' => ['status' => $status],
        ]);
    }

    /**
     * Affiche le formulaire de création d'un profil.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', ProviderProfile::class);

        return Inertia::render('pro/ProfileCreate', [
            'categories' => $this->categories(),
            'businesses' => $this->availableBusinesses($request->user()),
        ]);
    }

    /**
     * Enregistre un nouveau profil professionnel.
     */
    public function store(StoreProviderProfileRequest $request): RedirectResponse
    {
        $this->authorize('create', ProviderProfile::class);

        $validated = $request->validated();
        $validated = $this->normalizePhones($validated);
        $user = $request->user();

        // L'entreprise porteuse du profil doit être pilotable par l'utilisateur.
        $business = null;

        if (! empty($validated['business_id'])) {
            $business = Business::findOrFail($validated['business_id']);

            $this->authorize('manageProfiles', $business);
        }

        $entitlements = app(EntitlementService::class);

        if (! $entitlements->canCreateProfile($user, $business)) {
            app(SubscriptionNotifier::class)->limitReached(
                $business ?? $user,
                $entitlements->planForNewProfile($user, $business),
                'profils professionnels',
            );

            return back()->with('error', 'Limite de profils atteinte pour votre formule. Passez à une formule supérieure pour créer un nouveau profil.');
        }

        $logoPath = $request->hasFile('logo')
            ? $request->file('logo')->store('profiles/logos', 'public')
            : null;

        $profile = DB::transaction(function () use ($validated, $user, $business, $logoPath) {
            $profile = ProviderProfile::create([
                'user_id' => $user->id,
                'business_id' => $business?->id,
                'category_id' => $validated['category_id'],
                'subcategory_id' => $validated['subcategory_id'] ?? null,
                'business_name' => $validated['business_name'],
                'slug' => SlugGenerator::unique('provider_profiles', $validated['business_name']),
                'description' => $validated['description'] ?? null,
                'logo_path' => $logoPath,
                'phone' => $validated['phone'] ?? null,
                'whatsapp' => $validated['whatsapp'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'],
                'commune' => $validated['commune'] ?? null,
                'quartier' => $validated['quartier'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'years_experience' => $validated['years_experience'] ?? null,
                'response_time_hours' => $validated['response_time_hours'] ?? null,
            ]);

            // Un profil créé est immédiatement placé dans la file de vérification.
            $profile->forceFill([
                'status' => ProfileStatus::Pending,
                'submitted_at' => now(),
            ])->save();

            // La zone d'intervention principale reprend la localisation du profil.
            ServiceArea::create([
                'provider_profile_id' => $profile->id,
                'city' => $profile->city,
                'commune' => $profile->commune,
                'quartier' => $profile->quartier,
                'is_primary' => true,
            ]);

            // Abonnement FREE automatique pour les profils indépendants.
            if ($business === null) {
                app(SubscriptionService::class)->ensureFreeSubscription($profile, $user);
            }

            // Horaires d'ouverture par défaut : du lundi au samedi.
            for ($day = 1; $day <= 6; $day++) {
                ProviderProfileHour::create([
                    'provider_profile_id' => $profile->id,
                    'day_of_week' => $day,
                    'opens_at' => '08:00',
                    'closes_at' => '18:00',
                    'is_closed' => false,
                ]);
            }

            ProviderProfileHour::create([
                'provider_profile_id' => $profile->id,
                'day_of_week' => 0,
                'opens_at' => null,
                'closes_at' => null,
                'is_closed' => true,
            ]);

            return $profile;
        });

        return to_route('pro.profiles.show', $profile)->with('success', 'Votre profil a été créé et attend la vérification administrative.');
    }

    /**
     * Affiche la page de gestion d'un profil.
     */
    public function show(Request $request, ProviderProfile $profile): Response
    {
        $this->authorize('view', $profile);

        return Inertia::render('pro/ProfileEdit', $this->profilePayload($profile, $request->user()));
    }

    /**
     * Affiche le formulaire de modification d'un profil.
     */
    public function edit(Request $request, ProviderProfile $profile): Response
    {
        $this->authorize('update', $profile);

        return Inertia::render('pro/ProfileEdit', $this->profilePayload($profile, $request->user()));
    }

    /**
     * Met à jour les informations du profil.
     */
    public function update(UpdateProviderProfileRequest $request, ProviderProfile $profile): RedirectResponse
    {
        $this->authorize('update', $profile);

        $validated = $request->validated();
        $validated = $this->normalizePhones($validated);

        // Le rattachement à une entreprise doit rester autorisé.
        if (array_key_exists('business_id', $validated) && $validated['business_id']) {
            $business = Business::findOrFail($validated['business_id']);

            $this->authorize('manageProfiles', $business);
        } else {
            $validated['business_id'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('profiles/logos', 'public');
        }

        // Le nom commercial drive l'identifiant public du profil.
        if ($validated['business_name'] !== $profile->business_name) {
            $validated['slug'] = SlugGenerator::unique('provider_profiles', $validated['business_name'], $profile->id);
        }

        // L'adresse e-mail du profil est fixée à la création et ne peut plus être modifiée.
        unset($validated['email']);

        $profile->fill($validated)->save();

        return back()->with('success', 'Votre profil a été mis à jour.');
    }

    /**
     * Soumet le profil à la vérification administrative.
     */
    public function submit(Request $request, ProviderProfile $profile): RedirectResponse
    {
        $this->authorize('submit', $profile);

        if (! $profile->canBeSubmitted()) {
            return back()->with('error', 'Ce profil ne peut pas être soumis en l’état.');
        }

        $profile->forceFill([
            'status' => ProfileStatus::Pending,
            'submitted_at' => now(),
            'refusal_reason' => null,
        ])->save();

        return back()->with('success', 'Votre profil a été transmis pour vérification.');
    }

    /**
     * Désactive le profil sans le supprimer.
     */
    public function deactivate(Request $request, ProviderProfile $profile): RedirectResponse
    {
        $this->authorize('deactivate', $profile);

        $profile->forceFill(['is_active' => false])->save();

        return back()->with('success', 'Votre profil a été désactivé.');
    }

    /**
     * Ajoute un média à la galerie du profil.
     */
    public function storeMedia(Request $request, ProviderProfile $profile): RedirectResponse
    {
        $this->authorize('manageMedia', $profile);

        $entitlements = app(EntitlementService::class);

        if (! $entitlements->canUploadMedia($profile)) {
            app(SubscriptionNotifier::class)->limitReached(
                $profile,
                $entitlements->planFor($profile),
                'photos de galerie',
            );

            return back()->with('error', 'Votre formule ne permet pas d’ajouter des médias. Passez au plan Pro ou Business.');
        }

        $validated = $request->validate([
            'media' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=4096,max_height=4096'],
            'type' => ['nullable', 'string', 'in:photo,realisation,cover'],
            'caption' => ['nullable', 'string', 'max:200'],
        ]);

        $path = $request->file('media')->store('profiles/'.$profile->id, 'public');

        $profile->media()->create([
            'type' => $validated['type'] ?? MediaType::Realisation->value,
            'path' => $path,
            'disk' => 'public',
            'caption' => $validated['caption'] ?? null,
            'position' => $profile->media()->count(),
        ]);

        return back()->with('success', 'Le média a été ajouté.');
    }

    /**
     * Supprime un média de la galerie.
     */
    public function destroyMedia(ProviderProfile $profile, int $media): RedirectResponse
    {
        $this->authorize('manageMedia', $profile);

        $item = $profile->media()->whereKey($media)->firstOrFail();

        Storage::disk($item->disk)->delete($item->path);

        $item->delete();

        return back()->with('success', 'Le média a été supprimé.');
    }

    /**
     * Remplace les zones d'intervention du profil.
     */
    public function updateAreas(Request $request, ProviderProfile $profile): RedirectResponse
    {
        $this->authorize('manageAreas', $profile);

        $validated = $request->validate([
            'areas' => ['required', 'array', 'min:1', 'max:10'],
            'areas.*.city' => ['required', 'string', 'max:100'],
            'areas.*.commune' => ['nullable', 'string', 'max:100'],
            'areas.*.quartier' => ['nullable', 'string', 'max:100'],
        ]);

        $profile->serviceAreas()->delete();

        foreach ($validated['areas'] as $index => $area) {
            $profile->serviceAreas()->create([
                'city' => $area['city'],
                'commune' => $area['commune'] ?? null,
                'quartier' => $area['quartier'] ?? null,
                'is_primary' => $index === 0,
            ]);
        }

        return back()->with('success', 'Vos zones d’intervention ont été mises à jour.');
    }

    /**
     * Met à jour les horaires et les disponibilités du profil.
     */
    public function updateSchedule(Request $request, ProviderProfile $profile): RedirectResponse
    {
        $this->authorize('manageSchedule', $profile);

        $validated = $request->validate([
            'hours' => ['nullable', 'array'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
            'availabilities' => ['nullable', 'array'],
            'availabilities.*.date' => ['required', 'date', 'after_or_equal:today'],
            'availabilities.*.starts_at' => ['required', 'date_format:H:i'],
            'availabilities.*.ends_at' => ['required', 'date_format:H:i', 'after:availabilities.*.starts_at'],
            'availabilities.*.label' => ['nullable', 'string', 'max:120'],
        ]);

        if (array_key_exists('hours', $validated) && is_array($validated['hours'])) {
            $profile->hours()->delete();

            foreach ($validated['hours'] as $hour) {
                $profile->hours()->create([
                    'day_of_week' => $hour['day_of_week'],
                    'opens_at' => $hour['opens_at'] ?? null,
                    'closes_at' => $hour['closes_at'] ?? null,
                    'is_closed' => (bool) ($hour['is_closed'] ?? false),
                ]);
            }
        }

        if (array_key_exists('availabilities', $validated) && is_array($validated['availabilities'])) {
            $profile->availabilities()->delete();

            foreach ($validated['availabilities'] as $availability) {
                $profile->availabilities()->create([
                    'date' => $availability['date'],
                    'starts_at' => $availability['starts_at'],
                    'ends_at' => $availability['ends_at'],
                    'is_available' => true,
                    'label' => $availability['label'] ?? null,
                ]);
            }
        }

        return back()->with('success', 'Vos horaires et disponibilités ont été mis à jour.');
    }

    /**
     * Ajoute un service au profil.
     */
    public function storeService(StoreServiceRequest $request, ProviderProfile $profile): RedirectResponse
    {
        $this->authorize('manageServices', $profile);

        $entitlements = app(EntitlementService::class);

        if (! $entitlements->canAddService($profile)) {
            app(SubscriptionNotifier::class)->limitReached(
                $profile,
                $entitlements->planFor($profile),
                'services',
            );

            return back()->with('error', 'Limite de services atteinte pour votre formule. Passez à une formule supérieure.');
        }

        $validated = $request->validated();

        $profile->services()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price_min' => $validated['price_min'] ?? null,
            'price_max' => $validated['price_max'] ?? null,
            'price_type' => $validated['price_type'],
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $profile->services()->count(),
        ]);

        return back()->with('success', 'Le service a été ajouté.');
    }

    /**
     * Modifie un service du profil, y compris son activation.
     */
    public function updateService(UpdateServiceRequest $request, ProviderProfile $profile, int $service): RedirectResponse
    {
        $this->authorize('manageServices', $profile);

        $item = $profile->services()->whereKey($service)->firstOrFail();

        $validated = $request->validated();

        $item->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price_min' => $validated['price_min'] ?? null,
            'price_max' => $validated['price_max'] ?? null,
            'price_type' => $validated['price_type'],
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return back()->with('success', 'Le service a été mis à jour.');
    }

    /**
     * Supprime un service du profil.
     */
    public function destroyService(ProviderProfile $profile, int $service): RedirectResponse
    {
        $this->authorize('manageServices', $profile);

        $profile->services()->whereKey($service)->delete();

        return back()->with('success', 'Le service a été supprimé.');
    }

    /**
     * Normalise les numéros de téléphone au format international.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizePhones(array $validated): array
    {
        foreach (['phone', 'whatsapp'] as $key) {
            if (array_key_exists($key, $validated)) {
                $validated[$key] = $validated[$key] === null || $validated[$key] === ''
                    ? null
                    : MaliPhoneNumber::normalize($validated[$key]);
            }
        }

        return $validated;
    }

    // ─── Données transmises à l'interface ─────────────────────

    /**
     * Construit les données d'un profil pour l'interface de gestion.
     *
     * @return array<string, mixed>
     */
    private function profilePayload(ProviderProfile $profile, User $user): array
    {
        $profile->load(['category', 'subcategory', 'business', 'media', 'hours', 'availabilities', 'services']);

        return [
            'profile' => [
                ...$this->summarize($profile),
                'slug' => $profile->slug,
                'description' => $profile->description,
                'logo_path' => $profile->logo_path,
                'logo_url' => $profile->logo_path ? Storage::disk('public')->url($profile->logo_path) : null,
                'phone' => $profile->phone,
                'whatsapp' => $profile->whatsapp,
                'email' => $profile->email,
                'address' => $profile->address,
                'city' => $profile->city,
                'commune' => $profile->commune,
                'quartier' => $profile->quartier,
                'latitude' => $profile->latitude,
                'longitude' => $profile->longitude,
                'years_experience' => $profile->years_experience,
                'response_time_hours' => $profile->response_time_hours,
                'refusal_reason' => $profile->refusal_reason,
                'submitted_at' => $profile->submitted_at?->toIso8601String(),
                'verified_at' => $profile->verified_at?->toIso8601String(),
                'category' => $profile->category?->only(['id', 'name']),
                'subcategory' => $profile->subcategory?->only(['id', 'name']),
                'business' => $profile->business?->only(['id', 'name']),
                'areas' => $profile->serviceAreas->map(fn (ServiceArea $area) => [
                    'id' => $area->id,
                    'city' => $area->city,
                    'commune' => $area->commune,
                    'quartier' => $area->quartier,
                    'is_primary' => $area->is_primary,
                ])->values()->all(),
                'hours' => $profile->hours->map(fn (ProviderProfileHour $hour) => [
                    'day_of_week' => $hour->day_of_week,
                    'opens_at' => $hour->opens_at,
                    'closes_at' => $hour->closes_at,
                    'is_closed' => $hour->is_closed,
                ])->values()->all(),
                'availabilities' => $profile->availabilities->map(fn ($availability) => [
                    'id' => $availability->id,
                    'date' => $availability->date?->toDateString(),
                    'starts_at' => $availability->starts_at,
                    'ends_at' => $availability->ends_at,
                    'label' => $availability->label,
                ])->values()->all(),
                'media' => $profile->media->map(fn ($media) => [
                    'id' => $media->id,
                    'type' => $media->type->value,
                    'type_label' => $media->type->label(),
                    'url' => $media->url,
                    'caption' => $media->caption,
                ])->values()->all(),
                'services' => $profile->services->map(fn ($service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'price_type' => $service->price_type,
                    'price_min' => $service->price_min,
                    'price_max' => $service->price_max,
                    'duration_minutes' => $service->duration_minutes,
                    'formatted_price' => $service->formatted_price,
                    'is_active' => $service->is_active,
                ])->values()->all(),
            ],
            'categories' => $this->categories(),
            'statuses' => $this->statuses(),
            'canSubmit' => $user->can('provider_profiles.submit') && $profile->canBeSubmitted(),
            'canManageMedia' => $user->can('provider_profiles.media.manage'),
            'canManageSchedule' => $user->can('provider_profiles.schedule.manage'),
            'canManageAreas' => $user->can('provider_profiles.areas.manage'),
        ];
    }

    /**
     * Résume un profil pour les listes.
     *
     * @return array<string, mixed>
     */
    private function summarize(ProviderProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'business_name' => $profile->business_name,
            'slug' => $profile->slug,
            'status' => $profile->status->value,
            'status_label' => $profile->status->label(),
            'status_tone' => $profile->status->tone(),
            'is_active' => $profile->is_active,
            'is_publicly_visible' => $profile->isPubliclyVisible(),
            'city' => $profile->city,
            'commune' => $profile->commune,
            'category' => $profile->category?->name,
            'business' => $profile->business?->name,
            'rating_average' => (float) $profile->rating_average,
            'rating_count' => $profile->rating_count,
            'created_at' => $profile->created_at?->toIso8601String(),
        ];
    }

    /**
     * Requête des profils pilotés par l'utilisateur.
     */
    private function manageableProfilesQuery(User $user)
    {
        return ProviderProfile::query()->managedBy($user);
    }

    /**
     * Catégories et sous-catégories proposées dans les formulaires.
     *
     * @return array<int, array<string, mixed>>
     */
    private function categories(): array
    {
        return Category::query()
            ->with(['subcategories' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'subcategories' => $category->subcategories->map(fn ($subcategory) => [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Entreprises pilotables par l'utilisateur.
     *
     * @return array<int, array<string, mixed>>
     */
    private function availableBusinesses(User $user): array
    {
        $businessIds = $user->businessMemberships()
            ->where('status', 'active')
            ->pluck('business_id');

        return Business::query()
            ->whereIn('id', $businessIds)
            ->where('status', BusinessStatus::Verified)
            ->get()
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Statuts de vérification proposés à l'interface.
     *
     * @return array<int, array<string, string>>
     */
    private function statuses(): array
    {
        return collect(ProfileStatus::cases())
            ->map(fn (ProfileStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'tone' => $status->tone(),
            ])
            ->values()
            ->all();
    }
}
