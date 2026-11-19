<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use App\Services\Admin\AuditLogger;
use App\Support\Identity\PermissionCatalogue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des comptes utilisateurs depuis l'espace d'administration.
 *
 * Chaque action est soumise à une permission dédiée et à la politique
 * UserPolicy : aucun administrateur ne peut agir sur son propre compte.
 */
class UserController extends Controller
{
    /**
     * Affiche la liste filtrable des comptes.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status');
        $role = (string) $request->string('role');

        $paginator = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($role !== '', fn ($query) => $query->whereHas('roles', fn ($sub) => $sub->where('name', $role)))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/Users', [
            'users' => [
                'data' => collect($paginator->items())->map(fn (User $user) => $this->summarize($user))->all(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'links' => $paginator->linkCollection()->toArray(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'role' => $role,
            ],
            'statuses' => $this->statuses(),
            'roles' => $this->roles(),
            'canChangeStatus' => $request->user()->can('users.suspend'),
            'canAssignRoles' => $request->user()->can('users.assign_roles'),
        ]);
    }

    /**
     * Affiche le détail d'un compte.
     */
    public function show(Request $request, User $user): Response
    {
        $this->authorize('view', $user);

        $user->load('roles');

        return Inertia::render('admin/UserDetail', [
            'account' => [
                ...$this->summarize($user),
                'roles' => $user->getRoleNames()->values()->all(),
                'role_labels' => $user->getRoleNames()
                    ->map(fn (string $name) => UserRole::tryFrom($name)?->label() ?? $name)
                    ->values()
                    ->all(),
                'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values()->all(),
                'permission_labels' => $user->getAllPermissions()
                    ->pluck('name')
                    ->sort()
                    ->map(fn (string $name) => PermissionCatalogue::label($name))
                    ->values()
                    ->all(),
            ],
            'statuses' => $this->statuses(),
            'roles' => $this->roles(),
            'canChangeStatus' => $request->user()->can('users.suspend'),
            'canAssignRoles' => $request->user()->can('users.assign_roles'),
        ]);
    }

    /**
     * Change le statut d'un compte (activation, suspension, clôture).
     */
    public function updateStatus(
        UpdateUserStatusRequest $request,
        User $user,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('changeStatus', $user);

        $previous = $user->status;
        $user->forceFill(['status' => $request->enum('status', AccountStatus::class)])->save();

        $audit->log(
            'user.status.updated',
            sprintf(
                'Statut du compte de %s modifié de « %s » à « %s ».',
                $user->name,
                $previous->label(),
                $user->status->label(),
            ),
            $user,
            ['previous_status' => $previous->value, 'new_status' => $user->status->value],
            $request->user(),
            $request,
        );

        return back()->with('success', 'Le statut du compte a été mis à jour.');
    }

    /**
     * Attribue ou retire des rôles à un compte.
     *
     * Seuls les rôles autorisés par l'énumération sont acceptés et le dernier
     * super administrateur ne peut pas être rétrogradé.
     */
    public function updateRoles(
        UpdateUserRolesRequest $request,
        User $user,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('assignRoles', $user);

        $previousRoles = $user->getRoleNames()->values()->all();
        $roles = $request->validated('roles');

        if ($roles === []) {
            return back()->with('error', 'Aucun rôle valide n’a pu être attribué à ce compte.');
        }

        // Le dernier super administrateur ne peut pas perdre son rôle.
        if ($user->isSuperAdmin() && ! in_array(UserRole::SuperAdmin->value, $roles, true)) {
            $remaining = User::role(UserRole::SuperAdmin->value)->count();

            if ($remaining <= 1) {
                return back()->with('error', 'Le dernier super administrateur doit conserver son rôle.');
            }
        }

        $user->syncRoles($roles);

        $audit->log(
            'user.roles.updated',
            sprintf('Rôles du compte de %s mis à jour.', $user->name),
            $user,
            [
                'previous_roles' => $previousRoles,
                'new_roles' => $user->getRoleNames()->values()->all(),
            ],
            $request->user(),
            $request,
        );

        return back()->with('success', 'Les rôles du compte ont été mis à jour.');
    }

    /**
     * Résume un compte pour l'affichage dans les listes.
     *
     * @return array<string, mixed>
     */
    private function summarize(User $user): array
    {
        $subscription = $user->providerProfiles()
            ->with('subscriptions')
            ->get()
            ->flatMap(fn ($profile) => $profile->subscriptions)
            ->sortByDesc('created_at')
            ->first();

        $businessSubscription = $user->businessMemberships()
            ->with('business.subscriptions')
            ->get()
            ->flatMap(fn ($membership) => $membership->business?->subscriptions ?? collect())
            ->sortByDesc('created_at')
            ->first();

        $activeSubscription = $businessSubscription ?? $subscription;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status->value,
            'status_label' => $user->status->label(),
            'status_tone' => $user->status->tone(),
            'roles' => $user->getRoleNames()->values()->all(),
            'subscription_status' => $activeSubscription?->status->value,
            'subscription_status_label' => $activeSubscription?->status->label(),
            'subscription_plan' => $activeSubscription?->planModel?->name ?? $activeSubscription?->plan,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    /**
     * Liste des statuts disponibles.
     *
     * @return list<array{value: string, label: string}>
     */
    private function statuses(): array
    {
        return collect(AccountStatus::cases())
            ->map(fn (AccountStatus $status) => ['value' => $status->value, 'label' => $status->label()])
            ->values()
            ->all();
    }

    /**
     * Liste des rôles disponibles.
     *
     * @return list<array{value: string, label: string}>
     */
    private function roles(): array
    {
        return collect(UserRole::cases())
            ->map(fn (UserRole $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->all();
    }
}
