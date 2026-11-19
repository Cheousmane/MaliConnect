<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProfileStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewProviderProfileRequest;
use App\Models\ProviderProfile;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vérification administrative des profils professionnels.
 */
class ProviderProfileController extends Controller
{
    /**
     * Affiche la file de vérification des profils.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProviderProfile::class);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status');

        $paginator = ProviderProfile::query()
            ->with(['category', 'business', 'user'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('business_name', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%')
                        ->orWhere('commune', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('submitted_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/ProviderProfiles', [
            'profiles' => [
                'data' => collect($paginator->items())->map(fn (ProviderProfile $profile) => $this->summarize($profile))->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => collect(ProfileStatus::cases())
                ->map(fn (ProfileStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'tone' => $status->tone(),
                ])
                ->values()
                ->all(),
            'pendingCount' => ProviderProfile::pendingReview()->count(),
        ]);
    }

    /**
     * Affiche le dossier de vérification d'un profil.
     */
    public function show(Request $request, ProviderProfile $profile): Response
    {
        $this->authorize('view', $profile);

        $profile->load(['category', 'subcategory', 'business', 'media', 'hours', 'availabilities', 'services', 'user']);

        return Inertia::render('admin/ProviderProfileDetail', [
            'profile' => [
                ...$this->summarize($profile),
                'description' => $profile->description,
                'phone' => $profile->phone,
                'whatsapp' => $profile->whatsapp,
                'email' => $profile->email,
                'address' => $profile->address,
                'quartier' => $profile->quartier,
                'years_experience' => $profile->years_experience,
                'refusal_reason' => $profile->refusal_reason,
                'submitted_at' => $profile->submitted_at?->toIso8601String(),
                'verified_at' => $profile->verified_at?->toIso8601String(),
                'owner' => $profile->user?->only(['id', 'name', 'email', 'created_at']),
                'category' => $profile->category?->only(['id', 'name']),
                'subcategory' => $profile->subcategory?->only(['id', 'name']),
                'business' => $profile->business?->only(['id', 'name', 'status']),
                'areas' => $profile->serviceAreas->map(fn ($area) => [
                    'city' => $area->city,
                    'commune' => $area->commune,
                    'quartier' => $area->quartier,
                    'is_primary' => $area->is_primary,
                ])->values()->all(),
                'hours' => $profile->hours->map(fn ($hour) => [
                    'day_of_week' => $hour->day_of_week,
                    'opens_at' => $hour->opens_at,
                    'closes_at' => $hour->closes_at,
                    'is_closed' => $hour->is_closed,
                ])->values()->all(),
                'availabilities' => $profile->availabilities->map(fn ($availability) => [
                    'date' => $availability->date?->toDateString(),
                    'starts_at' => $availability->starts_at,
                    'ends_at' => $availability->ends_at,
                ])->values()->all(),
                'media' => $profile->media->map(fn ($media) => [
                    'id' => $media->id,
                    'type_label' => $media->type->label(),
                    'url' => $media->url,
                    'caption' => $media->caption,
                ])->values()->all(),
                'services' => $profile->services->map(fn ($service) => [
                    'name' => $service->name,
                    'description' => $service->description,
                    'price_type' => $service->price_type,
                    'price_min' => $service->price_min,
                ])->values()->all(),
            ],
            'statuses' => collect(ProfileStatus::cases())
                ->map(fn (ProfileStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'tone' => $status->tone(),
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Enregistre une décision de vérification.
     */
    public function review(
        ReviewProviderProfileRequest $request,
        ProviderProfile $profile,
        AuditLogger $audit,
    ): RedirectResponse {
        $decision = $request->validated('decision');
        $previous = $profile->status;

        match ($decision) {
            'approve' => $this->approve($request, $profile),
            'refuse' => $this->refuse($request, $profile),
            'suspend' => $this->suspend($request, $profile),
        };

        $decisionLabel = match ($decision) {
            'approve' => 'approuvé',
            'refuse' => 'refusé',
            'suspend' => 'suspendu',
            default => $decision,
        };

        $audit->log(
            'provider_profile.'.$decision.'d',
            sprintf(
                'Profil « %s » %s (statut %s → %s).',
                $profile->business_name,
                $decisionLabel,
                $previous->label(),
                $profile->status->label(),
            ),
            $profile,
            [
                'previous_status' => $previous->value,
                'new_status' => $profile->status->value,
                'decision' => $decision,
                'reason' => $request->validated('reason'),
            ],
            $request->user(),
            $request,
        );

        return back()->with('success', 'La décision a été enregistrée.');
    }

    /**
     * Valide un profil.
     */
    private function approve(ReviewProviderProfileRequest $request, ProviderProfile $profile): void
    {
        $this->authorize('verify', $profile);

        $profile->forceFill([
            'status' => ProfileStatus::Verified,
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
            'refusal_reason' => null,
        ])->save();
    }

    /**
     * Refuse un profil.
     */
    private function refuse(ReviewProviderProfileRequest $request, ProviderProfile $profile): void
    {
        $this->authorize('refuse', $profile);

        $profile->forceFill([
            'status' => ProfileStatus::Refused,
            'refusal_reason' => $request->validated('reason'),
        ])->save();
    }

    /**
     * Suspend un profil déjà validé.
     */
    private function suspend(ReviewProviderProfileRequest $request, ProviderProfile $profile): void
    {
        $this->authorize('suspend', $profile);

        $profile->forceFill([
            'status' => ProfileStatus::Suspended,
            'refusal_reason' => $request->validated('reason'),
        ])->save();
    }

    /**
     * Résume un profil pour l'interface d'administration.
     *
     * @return array<string, mixed>
     */
    private function summarize(ProviderProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'business_name' => $profile->business_name,
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
            'submitted_at' => $profile->submitted_at?->toIso8601String(),
            'verified_at' => $profile->verified_at?->toIso8601String(),
        ];
    }
}
