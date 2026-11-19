<?php

namespace App\Policies;

use App\Models\BusinessMember;
use App\Models\PricingSheet;
use App\Models\ProviderProfile;
use App\Models\User;

/**
 * Autorisations sur les fiches tarifaires.
 *
 * Le propriétaire du profil et les collaborateurs habilités de l'entreprise
 * porteuse peuvent gérer les fiches. Un collaborateur « agent » ne peut pas
 * les modifier.
 */
class PricingSheetPolicy
{
    /**
     * Autorise l'accès à la liste des fiches.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('provider_profiles.services.manage')
            || $user->businessMemberships()->where('status', 'active')->exists();
    }

    /**
     * Autorise la création d'une fiche pour un profil donné.
     */
    public function create(User $user, ProviderProfile $profile): bool
    {
        return $this->managesProfile($user, $profile);
    }

    /**
     * Autorise la consultation d'une fiche.
     */
    public function view(User $user, PricingSheet $sheet): bool
    {
        return $this->managesSheet($user, $sheet);
    }

    /**
     * Autorise la modification d'une fiche.
     */
    public function update(User $user, PricingSheet $sheet): bool
    {
        return $this->managesSheet($user, $sheet);
    }

    /**
     * Autorise la suppression d'une fiche.
     */
    public function delete(User $user, PricingSheet $sheet): bool
    {
        return $this->managesSheet($user, $sheet);
    }

    /**
     * Autorise la duplication d'une fiche.
     */
    public function duplicate(User $user, PricingSheet $sheet): bool
    {
        return $this->managesSheet($user, $sheet);
    }

    /**
     * Autorise le changement de statut d'une fiche.
     */
    public function updateStatus(User $user, PricingSheet $sheet): bool
    {
        return $this->managesSheet($user, $sheet);
    }

    /**
     * Autorise la gestion des prestations d'une fiche.
     */
    public function manageItems(User $user, PricingSheet $sheet): bool
    {
        return $this->managesSheet($user, $sheet);
    }

    /**
     * Vérifie que l'utilisateur pilote la fiche.
     */
    private function managesSheet(User $user, PricingSheet $sheet): bool
    {
        $profile = $sheet->providerProfile;

        return $profile !== null && $this->managesProfile($user, $profile);
    }

    /**
     * Vérifie que l'utilisateur pilote le profil.
     */
    private function managesProfile(User $user, ProviderProfile $profile): bool
    {
        // Le propriétaire doit disposer de la permission de gérer les services.
        if ($profile->user_id === $user->id) {
            return $user->can('provider_profiles.services.manage');
        }

        if ($profile->business_id === null) {
            return false;
        }

        /** @var BusinessMember|null $membership */
        $membership = $user->businessMemberships()
            ->where('business_id', $profile->business_id)
            ->where('status', 'active')
            ->first();

        return $membership?->canManageProfiles() ?? false;
    }
}
