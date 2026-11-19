<?php

namespace App\Policies;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;

/**
 * Autorisations sur les entreprises et leurs collaborateurs.
 */
class BusinessPolicy
{
    /**
     * Autorise la consultation de la liste des entreprises.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('businesses.view.all') || $user->can('businesses.create');
    }

    /**
     * Autorise la consultation d'une entreprise.
     */
    public function view(User $user, Business $business): bool
    {
        return $user->can('businesses.view.all') || $this->membership($user, $business) !== null;
    }

    /**
     * Autorise la création d'une entreprise.
     */
    public function create(User $user): bool
    {
        return $user->can('businesses.create');
    }

    /**
     * Autorise la modification d'une entreprise.
     */
    public function update(User $user, Business $business): bool
    {
        if ($user->can('businesses.update')) {
            return true;
        }

        if ($business->status === BusinessStatus::Suspended) {
            return false;
        }

        return $this->membership($user, $business)?->role->canManageMembers() ?? false;
    }

    /**
     * Autorise la gestion des collaborateurs de l'entreprise.
     */
    public function manageMembers(User $user, Business $business): bool
    {
        if ($user->can('businesses.manage_members')) {
            return true;
        }

        if ($business->status === BusinessStatus::Suspended) {
            return false;
        }

        return $this->membership($user, $business)?->role->canManageMembers() ?? false;
    }

    /**
     * Autorise la gestion des profils portés par l'entreprise.
     */
    public function manageProfiles(User $user, Business $business): bool
    {
        if ($user->can('businesses.manage_profiles')) {
            return true;
        }

        if ($business->status === BusinessStatus::Suspended) {
            return false;
        }

        return $this->membership($user, $business)?->canManageProfiles() ?? false;
    }

    /**
     * Autorise la validation ou le refus administratif.
     */
    public function verify(User $user, Business $business): bool
    {
        return $user->can('businesses.verify');
    }

    /**
     * Autorise la suspension administrative.
     */
    public function suspend(User $user, Business $business): bool
    {
        return $user->can('businesses.suspend');
    }

    /**
     * Autorise la réactivation administrative.
     */
    public function activate(User $user, Business $business): bool
    {
        return $user->can('businesses.activate');
    }

    /**
     * Retourne la collaboration de l'utilisateur dans l'entreprise.
     */
    private function membership(User $user, Business $business): ?BusinessMember
    {
        return $business->members()->where('user_id', $user->id)->first();
    }
}
