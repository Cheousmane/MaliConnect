<?php

namespace App\Http\Controllers\Pro;

use App\Enums\BusinessMemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pro\StoreBusinessMemberRequest;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;
use App\Services\Monetization\EntitlementService;
use App\Services\Monetization\SubscriptionNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gestion des collaborateurs d'une entreprise.
 */
class BusinessMemberController extends Controller
{
    /**
     * Recherche des comptes pouvant rejoindre l'entreprise.
     */
    public function search(Request $request, Business $business): JsonResponse
    {
        $this->authorize('manageMembers', $business);

        $search = trim((string) $request->string('q'));

        $memberIds = $business->members()->pluck('user_id');

        $users = User::query()
            ->whereNotIn('id', $memberIds)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json(['data' => $users]);
    }

    /**
     * Ajoute un collaborateur à l'entreprise.
     */
    public function store(StoreBusinessMemberRequest $request, Business $business): RedirectResponse
    {
        $this->authorize('manageMembers', $business);

        $entitlement = app(EntitlementService::class);

        if (! $entitlement->canManageCollaboratorsForBusiness($business)) {
            app(SubscriptionNotifier::class)->limitReached(
                $business,
                $entitlement->planForBusiness($business),
                'collaborateurs',
            );

            return back()->with('error', 'La gestion des collaborateurs nécessite une formule BUSINESS.');
        }

        $validated = $request->validated();

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $validated['user_id'],
            'role' => $validated['role'],
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return back()->with('success', 'Le collaborateur a été ajouté.');
    }

    /**
     * Modifie le rôle d'un collaborateur.
     */
    public function update(Request $request, Business $business, BusinessMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $business);

        // Le collaborateur doit appartenir à l'entreprise ciblée par l'URL.
        abort_unless((int) $member->business_id === (int) $business->id, 404);

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:owner,manager,agent'],
        ]);

        // Un collaborateur ne peut pas modifier son propre rôle.
        if ($member->user_id === $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas modifier votre propre rôle.');
        }

        // L'entreprise doit conserver au moins un responsable.
        if ($member->role === BusinessMemberRole::Owner && $validated['role'] !== BusinessMemberRole::Owner->value) {
            $owners = $business->members()->where('role', BusinessMemberRole::Owner->value)->count();

            if ($owners <= 1) {
                return back()->with('error', 'L’entreprise doit conserver au moins un responsable.');
            }
        }

        $member->update(['role' => $validated['role']]);

        return back()->with('success', 'Le rôle du collaborateur a été mis à jour.');
    }

    /**
     * Retire un collaborateur de l'entreprise.
     */
    public function destroy(Request $request, Business $business, BusinessMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $business);

        // Le collaborateur doit appartenir à l'entreprise ciblée par l'URL.
        abort_unless((int) $member->business_id === (int) $business->id, 404);

        // Le dernier responsable ne peut pas quitter l'entreprise.
        if ($member->role === BusinessMemberRole::Owner) {
            $owners = $business->members()->where('role', BusinessMemberRole::Owner->value)->count();

            if ($owners <= 1) {
                return back()->with('error', 'L’entreprise doit conserver au moins un responsable.');
            }
        }

        $member->delete();

        return back()->with('success', 'Le collaborateur a été retiré.');
    }
}
