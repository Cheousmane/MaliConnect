<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BusinessStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBusinessStatusRequest;
use App\Models\Business;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion administrative des entreprises.
 */
class BusinessController extends Controller
{
    /**
     * Affiche la liste filtrable des entreprises.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Business::class);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status');

        $paginator = Business::query()
            ->with('owner:id,name')
            ->withCount(['members', 'profiles'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%'.$search.'%')
                        ->orWhere('legal_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/Businesses', [
            'businesses' => [
                'data' => collect($paginator->items())->map(fn (Business $business) => $this->summarize($business))->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => $this->statuses(),
            'pendingCount' => Business::query()->where('status', BusinessStatus::Pending)->count(),
        ]);
    }

    /**
     * Affiche le dossier d'une entreprise.
     */
    public function show(Request $request, Business $business): Response
    {
        $this->authorize('view', $business);

        $business->load([
            'owner:id,name,email,phone',
            'members.user:id,name,email',
            'profiles' => fn ($query) => $query->with('category:id,name')->latest(),
            'verifiedBy:id,name',
        ]);

        return Inertia::render('admin/BusinessDetail', [
            'business' => [
                ...$this->summarize($business),
                'legal_name' => $business->legal_name,
                'description' => $business->description,
                'phone' => $business->phone,
                'email' => $business->email,
                'address' => $business->address,
                'city' => $business->city,
                'commune' => $business->commune,
                'submitted_at' => $business->submitted_at?->toIso8601String(),
                'verified_at' => $business->verified_at?->toIso8601String(),
                'verified_by' => $business->verifiedBy?->name,
                'refusal_reason' => $business->refusal_reason,
                'owner' => $business->owner?->only(['id', 'name', 'email', 'phone']),
                'members' => $business->members->map(fn ($member) => [
                    'id' => $member->id,
                    'name' => $member->user?->name,
                    'email' => $member->user?->email,
                    'role' => $member->role->value,
                    'role_label' => $member->role->label(),
                    'status' => $member->status,
                ])->values()->all(),
                'profiles' => $business->profiles->map(fn ($profile) => [
                    'id' => $profile->id,
                    'business_name' => $profile->business_name,
                    'category' => $profile->category?->name,
                    'status' => $profile->status->value,
                    'status_label' => $profile->status->label(),
                    'status_tone' => $profile->status->tone(),
                    'is_active' => $profile->is_active,
                ])->values()->all(),
            ],
            'statuses' => $this->statuses(),
        ]);
    }

    /**
     * Enregistre une décision administrative sur une entreprise.
     */
    public function updateStatus(
        UpdateBusinessStatusRequest $request,
        Business $business,
        AuditLogger $audit,
    ): RedirectResponse {
        $decision = $request->validated('decision');

        match ($decision) {
            'verify' => $this->authorize('verify', $business),
            'refuse' => $this->authorize('verify', $business),
            'suspend' => $this->authorize('suspend', $business),
            'activate' => $this->authorize('activate', $business),
        };

        $previous = $business->status;

        DB::transaction(function () use ($request, $business, $decision, $previous, $audit) {
            $attributes = match ($decision) {
                'verify' => [
                    'status' => BusinessStatus::Verified,
                    'verified_at' => now(),
                    'verified_by' => $request->user()->id,
                    'refusal_reason' => null,
                ],
                'refuse' => [
                    'status' => BusinessStatus::Refused,
                    'refusal_reason' => $request->validated('reason'),
                ],
                'suspend' => [
                    'status' => BusinessStatus::Suspended,
                    'refusal_reason' => $request->validated('reason'),
                ],
                'activate' => [
                    'status' => BusinessStatus::Verified,
                    'refusal_reason' => null,
                ],
            };

            $business->forceFill($attributes)->save();

            $audit->log(
                'business.status.updated',
                sprintf(
                    'Statut de l’entreprise « %s » modifié de « %s » à « %s ».',
                    $business->name,
                    $previous->label(),
                    $business->status->label(),
                ),
                $business,
                [
                    'previous_status' => $previous->value,
                    'new_status' => $business->status->value,
                    'decision' => $decision,
                    'reason' => $request->validated('reason'),
                ],
                $request->user(),
                $request,
            );
        });

        return back()->with('success', 'Le statut de l’entreprise a été mis à jour.');
    }

    /**
     * Résume une entreprise pour l'interface d'administration.
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
            'owner' => $business->owner?->name,
            'members_count' => $business->members_count ?? $business->members->count(),
            'profiles_count' => $business->profiles_count ?? $business->profiles->count(),
            'created_at' => $business->created_at?->toIso8601String(),
        ];
    }

    /**
     * Liste des statuts disponibles.
     *
     * @return list<array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return collect(BusinessStatus::cases())
            ->map(fn (BusinessStatus $status) => ['value' => $status->value, 'label' => $status->label()])
            ->values()
            ->all();
    }
}
