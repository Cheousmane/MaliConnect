<?php

namespace App\Http\Controllers\Pro;

use App\Enums\BusinessMemberRole;
use App\Enums\BusinessStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pro\StoreBusinessRequest;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\ProviderProfile;
use App\Support\Identity\SlugGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des entreprises dans l'espace prestataire.
 */
class BusinessController extends Controller
{
    /**
     * Affiche la liste des entreprises de l'utilisateur.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Business::class);

        $user = $request->user();

        $businesses = Business::query()
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereIn('id', $user->businessMemberships()->pluck('business_id'));
            })
            ->withCount(['members', 'profiles'])
            ->latest()
            ->get()
            ->map(fn (Business $business) => $this->summarize($business));

        return Inertia::render('pro/Businesses', [
            'businesses' => $businesses->values()->all(),
            'canCreate' => $user->can('businesses.create'),
        ]);
    }

    /**
     * Affiche le formulaire de création d'une entreprise.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Business::class);

        return Inertia::render('pro/BusinessCreate');
    }

    /**
     * Enregistre une nouvelle entreprise.
     */
    public function store(StoreBusinessRequest $request): RedirectResponse
    {
        $this->authorize('create', Business::class);

        $validated = $request->validated();
        $user = $request->user();

        $logoPath = $request->hasFile('logo')
            ? $request->file('logo')->store('businesses/logos', 'public')
            : null;

        $business = DB::transaction(function () use ($validated, $user, $logoPath) {
            $business = Business::create([
                'owner_id' => $user->id,
                'name' => $validated['name'],
                'slug' => SlugGenerator::unique('businesses', $validated['name']),
                'legal_name' => $validated['legal_name'] ?? null,
                'description' => $validated['description'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'],
                'commune' => $validated['commune'] ?? null,
                'logo_path' => $logoPath,
            ]);

            // L'entreprise est placée dans la file de vérification administrative.
            $business->forceFill([
                'status' => BusinessStatus::Pending,
                'submitted_at' => now(),
            ])->save();

            // Le créateur est responsable de l'entreprise.
            BusinessMember::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'role' => BusinessMemberRole::Owner,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            // Abonnement FREE automatique pour l'entreprise.
            app(\App\Services\Monetization\SubscriptionService::class)->ensureFreeSubscription($business, $user);

            return $business;
        });

        return to_route('pro.businesses.show', $business)->with('success', 'Votre entreprise a été créée.');
    }

    /**
     * Affiche la fiche d'une entreprise et ses collaborateurs.
     */
    public function show(Request $request, Business $business): Response
    {
        $this->authorize('view', $business);

        $business->load(['members.user', 'profiles.category', 'owner']);

        $user = $request->user();
        $membership = $business->members->firstWhere('user_id', $user->id);

        return Inertia::render('pro/BusinessShow', [
            'business' => [
                ...$this->summarize($business),
                'legal_name' => $business->legal_name,
                'description' => $business->description,
                'phone' => $business->phone,
                'email' => $business->email,
                'address' => $business->address,
                'logo_path' => $business->logo_path,
                'refusal_reason' => $business->refusal_reason,
                'owner' => $business->owner?->only(['id', 'name', 'email']),
                'members' => $business->members->map(fn (BusinessMember $member) => [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->user?->name,
                    'email' => $member->user?->email,
                    'role' => $member->role->value,
                    'role_label' => $member->role->label(),
                    'status' => $member->status,
                    'joined_at' => $member->joined_at?->toIso8601String(),
                ])->values()->all(),
                'profiles' => $business->profiles->map(fn (ProviderProfile $profile) => [
                    'id' => $profile->id,
                    'business_name' => $profile->business_name,
                    'status' => $profile->status->value,
                    'status_label' => $profile->status->label(),
                    'status_tone' => $profile->status->tone(),
                    'category' => $profile->category?->name,
                    'is_active' => $profile->is_active,
                ])->values()->all(),
            ],
            'roles' => collect(BusinessMemberRole::cases())
                ->map(fn (BusinessMemberRole $role) => [
                    'value' => $role->value,
                    'label' => $role->label(),
                ])
                ->values()
                ->all(),
            'myRole' => $membership?->role->value,
            'canManageMembers' => $user->can('businesses.manage_members') || ($membership?->canManageMembers() ?? false),
            'canManageProfiles' => $user->can('businesses.manage_profiles') || ($membership?->canManageProfiles() ?? false),
        ]);
    }

    /**
     * Met à jour les informations de l'entreprise.
     */
    public function update(Request $request, Business $business): RedirectResponse
    {
        $this->authorize('update', $business);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($business->logo_path) {
                Storage::disk('public')->delete($business->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('businesses/logos', 'public');
        }

        if ($validated['name'] !== $business->name) {
            $validated['slug'] = SlugGenerator::unique('businesses', $validated['name'], $business->id);
        }

        $business->fill($validated)->save();

        return back()->with('success', 'Les informations de l’entreprise ont été mises à jour.');
    }

    /**
     * Résume une entreprise pour les listes.
     *
     * @return array<string, mixed>
     */
    private function summarize(Business $business): array
    {
        return [
            'id' => $business->id,
            'name' => $business->name,
            'slug' => $business->slug,
            'status' => $business->status->value,
            'status_label' => $business->status->label(),
            'status_tone' => $business->status->tone(),
            'city' => $business->city,
            'commune' => $business->commune,
            'members_count' => $business->members_count ?? $business->members()->count(),
            'profiles_count' => $business->profiles_count ?? $business->profiles()->count(),
            'created_at' => $business->created_at?->toIso8601String(),
        ];
    }
}
