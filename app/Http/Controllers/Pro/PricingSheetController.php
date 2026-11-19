<?php

namespace App\Http\Controllers\Pro;

use App\Enums\PricingSheetStatus;
use App\Enums\PricingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pro\ReorderPricingItemsRequest;
use App\Http\Requests\Pro\StorePricingItemRequest;
use App\Http\Requests\Pro\StorePricingSheetRequest;
use App\Http\Requests\Pro\UpdatePricingItemRequest;
use App\Http\Requests\Pro\UpdatePricingSheetRequest;
use App\Http\Requests\Pro\UpdatePricingSheetStatusRequest;
use App\Models\Category;
use App\Models\PricingItem;
use App\Models\PricingSheet;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use App\Services\Monetization\EntitlementService;
use App\Services\Monetization\SubscriptionNotifier;
use App\Support\Pricing\PricingPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des fiches tarifaires dans l'espace professionnel.
 *
 * Toutes les opérations passent par PricingSheetPolicy : un professionnel ne
 * peut gérer que les fiches des profils qu'il pilote, directement ou via son
 * entreprise.
 */
class PricingSheetController extends Controller
{
    /**
     * Affiche toutes les fiches tarifaires pilotées par l'utilisateur.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PricingSheet::class);

        $user = $request->user();
        $profiles = $this->manageableProfiles($user);
        $profileIds = collect($profiles)->pluck('id');

        $status = $request->string('statut')->toString();
        $profileId = $request->integer('profil');

        $baseQuery = PricingSheet::query()->whereIn('provider_profile_id', $profileIds);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'published' => (clone $baseQuery)->where('status', PricingSheetStatus::Published)->count(),
            'draft' => (clone $baseQuery)->where('status', PricingSheetStatus::Draft)->count(),
            'archived' => (clone $baseQuery)->where('status', PricingSheetStatus::Archived)->count(),
        ];

        $sheets = (clone $baseQuery)
            ->with(['providerProfile:id,business_name,slug', 'category:id,name'])
            ->withCount(['items', 'availableItems'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($profileId > 0, fn ($query) => $query->where('provider_profile_id', $profileId))
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('pro/PricingSheets', [
            'sheets' => [
                'data' => collect($sheets->items())->map(fn (PricingSheet $sheet) => $this->summary($sheet))->all(),
                'current_page' => $sheets->currentPage(),
                'last_page' => $sheets->lastPage(),
                'total' => $sheets->total(),
            ],
            'profiles' => $profiles,
            'stats' => $stats,
            'statuses' => $this->statusOptions(),
            'filters' => [
                'statut' => $status,
                'profil' => $profileId > 0 ? $profileId : null,
            ],
        ]);
    }

    /**
     * Affiche le formulaire de création d'une fiche.
     */
    public function create(Request $request): Response
    {
        $this->authorize('viewAny', PricingSheet::class);

        $user = $request->user();
        $profiles = $this->manageableProfiles($user, true);

        $selectedProfile = null;

        if ($request->filled('profil')) {
            $selectedProfile = ProviderProfile::query()
                ->whereIn('id', collect($profiles)->pluck('id'))
                ->findOrFail($request->integer('profil'));

            $this->authorize('create', [PricingSheet::class, $selectedProfile]);
        }

        return Inertia::render('pro/PricingSheetCreate', [
            'profiles' => $profiles,
            'selectedProfile' => $selectedProfile?->only(['id', 'business_name', 'slug', 'category_id']),
            'categories' => $this->categories(),
            'pricingTypes' => PricingType::options(),
            'statuses' => $this->statusOptions(),
            'services' => $selectedProfile ? $this->servicesFor($selectedProfile) : [],
        ]);
    }

    /**
     * Enregistre une nouvelle fiche et ses prestations.
     */
    public function store(StorePricingSheetRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $profile = ProviderProfile::query()->findOrFail($validated['provider_profile_id']);

        $this->authorize('create', [PricingSheet::class, $profile]);

        $entitlements = app(EntitlementService::class);

        if (! $entitlements->canAddPricingSheet($profile)) {
            app(SubscriptionNotifier::class)->limitReached(
                $profile,
                $entitlements->planFor($profile),
                'fiches tarifaires',
            );

            return back()->with('error', 'Limite de fiches tarifaires atteinte pour votre formule. Passez à une formule supérieure.');
        }

        $status = PricingSheetStatus::tryFrom($validated['status'] ?? '') ?? PricingSheetStatus::Draft;
        $items = $validated['items'] ?? [];

        $this->ensureCanPublish($status, $items, $validated['valid_until'] ?? null);

        $sheet = DB::transaction(function () use ($validated, $profile, $status, $items) {
            $sheet = PricingSheet::create([
                'provider_profile_id' => $profile->id,
                'category_id' => $validated['category_id'] ?? $profile->category_id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'currency' => $validated['currency'] ?? 'XOF',
                'general_conditions' => $validated['general_conditions'] ?? null,
                'status' => $status,
                'valid_until' => $validated['valid_until'] ?? null,
                'published_at' => $status === PricingSheetStatus::Published ? now() : null,
                'archived_at' => $status === PricingSheetStatus::Archived ? now() : null,
            ]);

            $this->syncItems($sheet, $items);

            return $sheet;
        });

        return to_route('pro.pricing-sheets.show', $sheet)
            ->with('success', 'La fiche tarifaire a été créée.');
    }

    /**
     * Affiche la fiche et son formulaire de modification.
     */
    public function show(Request $request, PricingSheet $sheet): Response
    {
        $this->authorize('view', $sheet);

        $sheet->load(['providerProfile', 'category', 'items.service', 'items.category']);

        return Inertia::render('pro/PricingSheetEdit', [
            'sheet' => PricingPresenter::manageSheet($sheet),
            'categories' => $this->categories(),
            'pricingTypes' => PricingType::options(),
            'statuses' => $this->statusOptions(),
            'services' => $this->servicesFor($sheet->providerProfile),
        ]);
    }

    /**
     * Affiche un aperçu de la fiche tel qu'il sera présenté au public.
     *
     * L'aperçu reste réservé au professionnel qui pilote la fiche : une fiche
     * en brouillon n'est jamais accessible par son lien public.
     */
    public function preview(PricingSheet $sheet): Response
    {
        $this->authorize('view', $sheet);

        $sheet->load([
            'providerProfile',
            'category',
            'items.service',
            'items.category',
        ]);

        return Inertia::render('public/PricingSheet', [
            'sheet' => PricingPresenter::publicSheet($sheet),
            'preview' => true,
            'manageUrl' => route('pro.pricing-sheets.show', $sheet),
        ]);
    }

    /**
     * Met à jour la fiche et remplace ses prestations.
     */
    public function update(UpdatePricingSheetRequest $request, PricingSheet $sheet): RedirectResponse
    {
        $this->authorize('update', $sheet);

        $validated = $request->validated();
        $items = $validated['items'] ?? [];

        // Une fiche publiée ne doit jamais perdre sa dernière prestation
        // disponible, même lors d'une mise à jour globale du formulaire.
        if ($sheet->isPublished()) {
            $this->ensureCanPublish(
                PricingSheetStatus::Published,
                $items,
                $validated['valid_until'] ?? null,
                'items',
            );
        }

        DB::transaction(function () use ($sheet, $validated, $items) {
            $sheet->update([
                'category_id' => $validated['category_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'currency' => $validated['currency'] ?? 'XOF',
                'general_conditions' => $validated['general_conditions'] ?? null,
                'valid_until' => $validated['valid_until'] ?? null,
            ]);

            $this->syncItems($sheet, $items);
        });

        return back()->with('success', 'La fiche tarifaire a été mise à jour.');
    }

    /**
     * Supprime une fiche et ses prestations.
     */
    public function destroy(PricingSheet $sheet): RedirectResponse
    {
        $this->authorize('delete', $sheet);

        $sheet->delete();

        return to_route('pro.pricing-sheets.index')
            ->with('success', 'La fiche tarifaire a été supprimée.');
    }

    /**
     * Duplique une fiche en brouillon.
     */
    public function duplicate(PricingSheet $sheet): RedirectResponse
    {
        $this->authorize('duplicate', $sheet);

        $profile = $sheet->providerProfile;

        if ($profile !== null) {
            $entitlements = app(EntitlementService::class);

            if (! $entitlements->canAddPricingSheet($profile)) {
                app(SubscriptionNotifier::class)->limitReached(
                    $profile,
                    $entitlements->planFor($profile),
                    'fiches tarifaires',
                );

                return back()->with('error', 'Limite de fiches tarifaires atteinte pour votre formule. Passez à une formule supérieure.');
            }
        }

        $copy = $sheet->duplicate();

        return to_route('pro.pricing-sheets.show', $copy)
            ->with('success', 'La fiche a été dupliquée en brouillon.');
    }

    /**
     * Publie, dépublie ou archive une fiche.
     */
    public function updateStatus(UpdatePricingSheetStatusRequest $request, PricingSheet $sheet): RedirectResponse
    {
        $this->authorize('updateStatus', $sheet);

        $status = PricingSheetStatus::from($request->validated('status'));

        if ($status === PricingSheetStatus::Published) {
            $this->ensureCanPublish(
                $status,
                $sheet->items()->get()->toArray(),
                $sheet->valid_until?->toDateString(),
            );

            $sheet->forceFill([
                'status' => $status,
                'published_at' => $sheet->published_at ?? now(),
                'archived_at' => null,
            ])->save();

            return back()->with('success', 'La fiche est publiée.');
        }

        if ($status === PricingSheetStatus::Archived) {
            $sheet->forceFill([
                'status' => $status,
                'archived_at' => now(),
            ])->save();

            return back()->with('success', 'La fiche a été archivée.');
        }

        $sheet->forceFill([
            'status' => $status,
            'published_at' => null,
            'archived_at' => null,
        ])->save();

        return back()->with('success', 'La fiche est repassée en brouillon.');
    }

    /**
     * Ajoute une prestation à une fiche.
     */
    public function storeItem(StorePricingItemRequest $request, PricingSheet $sheet): RedirectResponse
    {
        $this->authorize('manageItems', $sheet);

        $validated = $request->validated();

        $sheet->items()->create([
            ...$this->itemAttributes($validated),
            'sort_order' => (int) $sheet->items()->max('sort_order') + 1,
        ]);

        return back()->with('success', 'La prestation a été ajoutée.');
    }

    /**
     * Modifie une prestation d'une fiche.
     */
    public function updateItem(UpdatePricingItemRequest $request, PricingSheet $sheet, PricingItem $item): RedirectResponse
    {
        $this->authorize('manageItems', $sheet);

        abort_unless($item->pricing_sheet_id === $sheet->id, 404);

        $item->update($this->itemAttributes($request->validated()));

        return back()->with('success', 'La prestation a été mise à jour.');
    }

    /**
     * Supprime une prestation d'une fiche.
     */
    public function destroyItem(PricingSheet $sheet, PricingItem $item): RedirectResponse
    {
        $this->authorize('manageItems', $sheet);

        abort_unless($item->pricing_sheet_id === $sheet->id, 404);

        if ($sheet->isPublished()) {
            $totalItems = $sheet->items()->count();
            $availableItems = $sheet->items()->where('is_available', true)->count();

            // La fiche publiée doit conserver au moins une prestation
            // disponible : supprimer la dernière rendrait la page vide.
            if ($totalItems <= 1 || ($item->is_available && $availableItems <= 1)) {
                return back()->with('error', 'Une fiche publiée doit conserver au moins une prestation disponible.');
            }
        }

        $item->delete();

        return back()->with('success', 'La prestation a été supprimée.');
    }

    /**
     * Réorganise les prestations d'une fiche.
     */
    public function reorderItems(ReorderPricingItemsRequest $request, PricingSheet $sheet): RedirectResponse
    {
        $this->authorize('manageItems', $sheet);

        $ids = $request->validated('items');
        $ownedIds = $sheet->items()->pluck('id')->all();

        if (count(array_diff($ids, $ownedIds)) > 0 || count($ids) !== count($ownedIds)) {
            throw ValidationException::withMessages([
                'items' => 'La liste des prestations est invalide.',
            ]);
        }

        DB::transaction(function () use ($sheet, $ids) {
            foreach ($ids as $position => $id) {
                $sheet->items()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return back()->with('success', 'L’ordre des prestations a été mis à jour.');
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Synchronise les prestations d'une fiche avec les données du formulaire.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(PricingSheet $sheet, array $items): void
    {
        $existing = $sheet->items()->get()->keyBy('id');
        $keptIds = [];

        foreach ($items as $position => $data) {
            $item = null;

            if (! empty($data['id'])) {
                $item = $existing->get((int) $data['id']);

                if ($item === null) {
                    throw ValidationException::withMessages([
                        'items' => 'Une prestation n’appartient pas à cette fiche.',
                    ]);
                }
            }

            $item ??= new PricingItem(['pricing_sheet_id' => $sheet->id]);

            $item->fill([
                ...$this->itemAttributes($data),
                'sort_order' => $position,
            ])->save();

            $keptIds[] = $item->id;
        }

        $sheet->items()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Normalise les attributs d'une prestation.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function itemAttributes(array $data): array
    {
        return [
            'service_id' => $data['service_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'pricing_type' => $data['pricing_type'],
            'price' => $data['price'] ?? null,
            'price_min' => $data['price_min'] ?? null,
            'price_max' => $data['price_max'] ?? null,
            'billing_unit' => $data['billing_unit'] ?? null,
            'conditions' => $data['conditions'] ?? null,
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? null,
            'is_available' => (bool) ($data['is_available'] ?? true),
            'requires_diagnostic' => (bool) ($data['requires_diagnostic'] ?? false),
        ];
    }

    /**
     * Vérifie qu'une fiche peut être publiée.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function ensureCanPublish(PricingSheetStatus $status, array $items, ?string $validUntil, string $itemErrorKey = 'status'): void
    {
        if ($status !== PricingSheetStatus::Published) {
            return;
        }

        $hasAvailableItem = collect($items)->contains(
            fn ($item) => (bool) ($item['is_available'] ?? true),
        );

        if (! $hasAvailableItem) {
            throw ValidationException::withMessages([
                $itemErrorKey => 'Ajoutez au moins une prestation disponible avant de publier la fiche.',
            ]);
        }

        if ($validUntil !== null && today()->gt(Carbon::parse($validUntil)->startOfDay())) {
            throw ValidationException::withMessages([
                'valid_until' => 'La date de validité est dépassée : mettez-la à jour avant de publier.',
            ]);
        }
    }

    /**
     * Liste des profils pilotés par l'utilisateur.
     *
     * @return array<int, array<string, mixed>>
     */
    private function manageableProfiles(User $user, bool $withServices = false): array
    {
        $query = ProviderProfile::query()
            ->managedBy($user)
            ->with('category:id,name')
            ->orderBy('business_name');

        if ($withServices) {
            $query->with([
                'services' => fn ($services) => $services
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->select(['id', 'provider_profile_id', 'name']),
            ]);
        }

        return $query
            ->get()
            ->filter(fn (ProviderProfile $profile) => $user->can('create', [PricingSheet::class, $profile]))
            ->map(function (ProviderProfile $profile) use ($withServices) {
                $data = [
                    'id' => $profile->id,
                    'business_name' => $profile->business_name,
                    'slug' => $profile->slug,
                    'category' => $profile->category?->name,
                    'category_id' => $profile->category_id,
                    'is_publicly_visible' => $profile->isPubliclyVisible(),
                ];

                if ($withServices) {
                    $data['services'] = $profile->services
                        ->map(fn (Service $service) => [
                            'id' => $service->id,
                            'name' => $service->name,
                        ])
                        ->values()
                        ->all();
                }

                return $data;
            })
            ->values()
            ->all();
    }

    /**
     * Services existants d'un profil, utilisables pour lier une prestation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function servicesFor(?ProviderProfile $profile): array
    {
        if ($profile === null) {
            return [];
        }

        return Service::query()
            ->where('provider_profile_id', $profile->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name'])
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Catégories actives proposées dans les formulaires.
     *
     * @return array<int, array<string, mixed>>
     */
    private function categories(): array
    {
        return Category::query()
            ->active()
            ->ordered()
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Statuts proposés dans l'interface.
     *
     * @return array<int, array<string, string>>
     */
    private function statusOptions(): array
    {
        return collect(PricingSheetStatus::cases())
            ->map(fn (PricingSheetStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'tone' => $status->tone(),
            ])
            ->values()
            ->all();
    }

    /**
     * Résumé d'une fiche pour la liste.
     *
     * @return array<string, mixed>
     */
    private function summary(PricingSheet $sheet): array
    {
        return [
            'id' => $sheet->id,
            'name' => $sheet->name,
            'status' => $sheet->status->value,
            'status_label' => $sheet->status->label(),
            'status_tone' => $sheet->status->tone(),
            'is_expired' => $sheet->isExpired(),
            'currency' => $sheet->currency,
            'valid_until' => $sheet->valid_until?->toDateString(),
            'items_count' => $sheet->items_count,
            'available_items_count' => $sheet->available_items_count,
            'profile' => $sheet->providerProfile?->only(['id', 'business_name', 'slug']),
            'category' => $sheet->category?->name,
            'updated_at' => $sheet->updated_at?->toIso8601String(),
            'public_url' => route('public.pricing-sheets.show', $sheet->public_token),
            'pdf_url' => route('public.pricing-sheets.pdf', $sheet->public_token),
        ];
    }
}
