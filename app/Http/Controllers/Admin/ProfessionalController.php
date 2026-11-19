<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProfileStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfessionalActivationRequest;
use App\Models\ProviderProfile;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Supervision administrative des profils professionnels.
 *
 * La vérification des dossiers reste portée par le contrôleur dédié ; ce
 * module donne une vue d'ensemble de tous les profils et permet de les
 * désactiver ou de les réactiver.
 */
class ProfessionalController extends Controller
{
    /**
     * Affiche la liste de tous les profils professionnels.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProviderProfile::class);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status');
        $active = (string) $request->string('active');

        $paginator = ProviderProfile::query()
            ->with(['category:id,name', 'business:id,name', 'user:id,name'])
            ->withCount(['serviceRequests', 'bookings', 'reviews'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('business_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($active === '1', fn ($query) => $query->where('is_active', true))
            ->when($active === '0', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/Professionals', [
            'profiles' => [
                'data' => collect($paginator->items())->map(fn (ProviderProfile $profile) => $this->summarize($profile))->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'active' => $active,
            ],
            'statuses' => collect(ProfileStatus::cases())
                ->map(fn (ProfileStatus $status) => ['value' => $status->value, 'label' => $status->label()])
                ->values()
                ->all(),
            'pendingCount' => ProviderProfile::query()->pendingReview()->count(),
        ]);
    }

    /**
     * Active ou désactive un profil professionnel.
     */
    public function updateActivation(
        UpdateProfessionalActivationRequest $request,
        ProviderProfile $profile,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('update', $profile);

        $profile->forceFill(['is_active' => $request->boolean('is_active')])->save();

        $audit->log(
            'provider_profile.activation.updated',
            sprintf(
                'Profil « %s » %s par l’administration.',
                $profile->business_name,
                $profile->is_active ? 'réactivé' : 'désactivé',
            ),
            $profile,
            ['is_active' => $profile->is_active],
            $request->user(),
            $request,
        );

        return back()->with(
            'success',
            $profile->is_active ? 'Le profil a été réactivé.' : 'Le profil a été désactivé.',
        );
    }

    /**
     * Résume un profil pour la liste d'administration.
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
            'owner' => $profile->user?->name,
            'requests_count' => $profile->service_requests_count,
            'bookings_count' => $profile->bookings_count,
            'reviews_count' => $profile->reviews_count,
            'created_at' => $profile->created_at?->toIso8601String(),
        ];
    }
}
