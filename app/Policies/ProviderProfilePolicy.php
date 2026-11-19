<?php

namespace App\Policies;

use App\Models\ProviderProfile;
use App\Models\User;

/**
 * Autorisations sur les profils professionnels.
 *
 * Un profil peut être piloté par son propriétaire ou par un collaborateur
 * habilité de l'entreprise qui le porte. L'administration intervient par
 * permissions dédiées (vérification, refus, suspension).
 */
class ProviderProfilePolicy
{
    /**
     * Autorise la consultation de la liste des profils.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('provider_profiles.view.all') || $user->can('provider_profiles.create');
    }

    /**
     * Autorise la consultation d'un profil.
     */
    public function view(User $user, ProviderProfile $profile): bool
    {
        return $this->managesProfile($user, $profile) || $user->can('provider_profiles.view.all');
    }

    /**
     * Autorise la création d'un profil.
     */
    public function create(User $user): bool
    {
        return $user->can('provider_profiles.create');
    }

    /**
     * Autorise la modification d'un profil.
     */
    public function update(User $user, ProviderProfile $profile): bool
    {
        if ($user->can('provider_profiles.update')) {
            return true;
        }

        return $this->managesProfile($user, $profile) && $user->can('provider_profiles.update.own');
    }

    /**
     * Autorise la soumission d'un profil à la vérification.
     */
    public function submit(User $user, ProviderProfile $profile): bool
    {
        return $this->managesProfile($user, $profile) && $user->can('provider_profiles.submit');
    }

    /**
     * Autorise la désactivation d'un profil.
     */
    public function deactivate(User $user, ProviderProfile $profile): bool
    {
        return $this->managesProfile($user, $profile) && $user->can('provider_profiles.deactivate.own');
    }

    /**
     * Autorise la validation administrative d'un profil.
     */
    public function verify(User $user, ProviderProfile $profile): bool
    {
        return $user->can('provider_profiles.verify');
    }

    /**
     * Autorise le refus administratif d'un profil.
     */
    public function refuse(User $user, ProviderProfile $profile): bool
    {
        return $user->can('provider_profiles.refuse');
    }

    /**
     * Autorise la suspension administrative d'un profil.
     */
    public function suspend(User $user, ProviderProfile $profile): bool
    {
        return $user->can('provider_profiles.suspend');
    }

    /**
     * Autorise la mise en avant d'un profil.
     */
    public function feature(User $user, ProviderProfile $profile): bool
    {
        return $user->can('provider_profiles.feature');
    }

    /**
     * Autorise la gestion des médias du profil.
     */
    public function manageMedia(User $user, ProviderProfile $profile): bool
    {
        return $this->managesProfile($user, $profile) && $user->can('provider_profiles.media.manage');
    }

    /**
     * Autorise la gestion des services du profil.
     */
    public function manageServices(User $user, ProviderProfile $profile): bool
    {
        return $this->managesProfile($user, $profile) && $user->can('provider_profiles.services.manage');
    }

    /**
     * Autorise la gestion des zones d'intervention du profil.
     */
    public function manageAreas(User $user, ProviderProfile $profile): bool
    {
        return $this->managesProfile($user, $profile) && $user->can('provider_profiles.areas.manage');
    }

    /**
     * Autorise la gestion des horaires et disponibilités du profil.
     */
    public function manageSchedule(User $user, ProviderProfile $profile): bool
    {
        return $this->managesProfile($user, $profile) && $user->can('provider_profiles.schedule.manage');
    }

    /**
     * Indique si l'utilisateur pilote le profil.
     *
     * Le propriétaire du profil et les collaborateurs habilités de l'entreprise
     * qui le porte peuvent le piloter.
     */
    private function managesProfile(User $user, ProviderProfile $profile): bool
    {
        return $profile->isManagedBy($user);
    }
}
