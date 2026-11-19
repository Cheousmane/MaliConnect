<?php

namespace App\Services\Monetization;

use App\Models\Business;
use App\Models\Plan;
use App\Models\PricingSheet;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;

/**
 * Contrôle côté serveur des fonctionnalités et limites des formules.
 *
 * Le masquage dans Vue ne constitue jamais une protection : chaque limite
 * est vérifiée ici avant toute action métier. Les valeurs proviennent de la
 * table `plans`, donc restent configurables depuis l'administration.
 *
 * Résolution du propriétaire :
 *   1. abonnement de l'entreprise qui porte le profil ;
 *   2. abonnement propre du profil indépendant ;
 *   3. formule FREE par défaut.
 */
final class EntitlementService
{
    // ─── Résolution du plan ──────────────────────────────────

    /**
     * Retourne le plan effectif d'un profil professionnel.
     */
    public function planFor(ProviderProfile $profile): Plan
    {
        return $this->planFromSubscription($this->subscriptionForProfile($profile));
    }

    /**
     * Retourne le plan effectif d'une entreprise.
     */
    public function planForBusiness(Business $business): Plan
    {
        return $this->planFromSubscription($business->activeSubscription());
    }

    /**
     * Retourne le plan effectif d'un professionnel indépendant.
     */
    public function planForUser(User $user): Plan
    {
        $subscription = Subscription::query()
            ->whereNull('business_id')
            ->whereHas('providerProfile', fn ($query) => $query->where('user_id', $user->id)->whereNull('business_id'))
            ->grantingAccess()
            ->latest('starts_at')
            ->latest('id')
            ->first();

        return $this->planFromSubscription($subscription);
    }

    /**
     * Retourne le plan applicable à la création d'un profil.
     */
    public function planForNewProfile(User $user, ?Business $business = null): Plan
    {
        return $business !== null
            ? $this->planForBusiness($business)
            : $this->planForUser($user);
    }

    /**
     * Abonnement effectif d'un profil : entreprise d'abord, profil ensuite.
     */
    public function subscriptionForProfile(ProviderProfile $profile): ?Subscription
    {
        if ($profile->business_id !== null) {
            $businessSubscription = $profile->business?->activeSubscription();

            if ($businessSubscription !== null) {
                return $businessSubscription;
            }
        }

        return $profile->activeSubscription();
    }

    /**
     * Convertit un abonnement en plan configurable.
     */
    public function planFromSubscription(?Subscription $subscription): Plan
    {
        if ($subscription?->planModel !== null) {
            return $subscription->planModel;
        }

        $code = $subscription?->plan;

        if (is_string($code) && $code !== '') {
            return Plan::query()->where('code', $code)->first() ?? $this->fallbackPlan($code);
        }

        return $this->freePlan();
    }

    /**
     * Formule FREE par défaut.
     */
    public function freePlan(): Plan
    {
        return Plan::query()->where('code', 'free')->first() ?? $this->fallbackPlan('free');
    }

    // ─── Limites de création ─────────────────────────────────

    /**
     * Indique si un profil peut être créé pour un propriétaire.
     */
    public function canCreateProfile(User $user, ?Business $business = null): bool
    {
        $plan = $this->planForNewProfile($user, $business);
        $used = $this->profilesUsed($user, $business);

        return $plan->max_profiles === 0 || $used < $plan->max_profiles;
    }

    /**
     * Nombre de profils déjà utilisés par le propriétaire.
     */
    public function profilesUsed(User $user, ?Business $business = null): int
    {
        if ($business !== null) {
            return $business->profiles()->count();
        }

        return ProviderProfile::query()
            ->where('user_id', $user->id)
            ->whereNull('business_id')
            ->count();
    }

    /**
     * Indique si un service peut être ajouté au profil.
     */
    public function canAddService(ProviderProfile $profile): bool
    {
        $plan = $this->planFor($profile);

        if ($plan->isUnlimitedServices()) {
            return true;
        }

        return $this->servicesUsed($profile) < $plan->max_services;
    }

    /**
     * Nombre de services décomptés pour le plan du profil.
     *
     * Une formule BUSINESS couvre tous les profils de l'entreprise : la
     * limite de 50 services s'applique donc au portefeuille global.
     */
    public function servicesUsed(ProviderProfile $profile): int
    {
        if ($this->isBusinessPlan($profile) && $profile->business_id !== null) {
            return Service::query()
                ->whereHas('providerProfile', fn ($query) => $query->where('business_id', $profile->business_id))
                ->count();
        }

        return $profile->services()->count();
    }

    /**
     * Indique si un média peut être ajouté au profil.
     */
    public function canUploadMedia(ProviderProfile $profile): bool
    {
        $plan = $this->planFor($profile);

        if (! $plan->has_gallery) {
            return false;
        }

        if ($plan->max_media === 0) {
            return true;
        }

        return $profile->media()->count() < $plan->max_media;
    }

    /**
     * Indique si une fiche tarifaire peut être créée pour le profil.
     */
    public function canAddPricingSheet(ProviderProfile $profile): bool
    {
        $plan = $this->planFor($profile);

        if (! $plan->has_simple_pricing_sheets && ! $plan->has_full_pricing_sheets) {
            return false;
        }

        if ($plan->isUnlimitedPricingSheets()) {
            return true;
        }

        return $this->pricingSheetsUsed($profile) < $plan->max_pricing_sheets;
    }

    /**
     * Nombre de fiches tarifaires décomptées pour le plan du profil.
     */
    public function pricingSheetsUsed(ProviderProfile $profile): int
    {
        if ($this->isBusinessPlan($profile) && $profile->business_id !== null) {
            return PricingSheet::query()
                ->whereHas('providerProfile', fn ($query) => $query->where('business_id', $profile->business_id))
                ->count();
        }

        return $profile->pricingSheets()->count();
    }

    // ─── Fonctionnalités ─────────────────────────────────────

    public function canManageCollaborators(ProviderProfile $profile): bool
    {
        return $this->planFor($profile)->has_collaborators;
    }

    public function canManageCollaboratorsForBusiness(Business $business): bool
    {
        return $this->planForBusiness($business)->has_collaborators;
    }

    public function hasAdvancedStats(ProviderProfile $profile): bool
    {
        return $this->planFor($profile)->has_advanced_stats;
    }

    public function hasAdvancedQuotes(ProviderProfile $profile): bool
    {
        return $this->planFor($profile)->has_advanced_quotes;
    }

    public function canManageBookings(ProviderProfile $profile): bool
    {
        return $this->planFor($profile)->has_bookings;
    }

    public function hasPrioritySupport(ProviderProfile $profile): bool
    {
        return $this->planFor($profile)->has_priority_support;
    }

    public function canHighlight(ProviderProfile $profile): bool
    {
        return $this->planFor($profile)->has_highlight_options;
    }

    public function hasProfileBadge(ProviderProfile $profile): bool
    {
        return $this->planFor($profile)->has_profile_badge;
    }

    public function hasCentralizedRequests(ProviderProfile $profile): bool
    {
        return $this->planFor($profile)->has_centralized_requests;
    }

    public function canUseFullPricingSheets(ProviderProfile $profile): bool
    {
        return $this->planFor($profile)->has_full_pricing_sheets;
    }

    public function canUseSimplePricingSheets(ProviderProfile $profile): bool
    {
        return $this->planFor($profile)->has_simple_pricing_sheets;
    }

    /**
     * Indique si le plan effectif du profil est porté par une entreprise.
     */
    public function isBusinessPlan(ProviderProfile $profile): bool
    {
        return $this->subscriptionForProfile($profile)?->business_id !== null;
    }

    // ─── Données pour l'interface ────────────────────────────

    /**
     * Limites utilisées et restantes pour un abonnement.
     *
     * @return list<array<string, mixed>>
     */
    public function limitsForSubscription(Subscription $subscription): array
    {
        $plan = $this->planFromSubscription($subscription);

        if ($subscription->business_id !== null && $subscription->business !== null) {
            $business = $subscription->business;

            $profilesUsed = $business->profiles()->count();
            $servicesUsed = Service::query()
                ->whereHas('providerProfile', fn ($query) => $query->where('business_id', $business->id))
                ->count();
            $pricingSheetsUsed = PricingSheet::query()
                ->whereHas('providerProfile', fn ($query) => $query->where('business_id', $business->id))
                ->count();

            return [
                $this->limitRow('profiles', 'Profils professionnels', $profilesUsed, $plan->max_profiles),
                $this->limitRow('services', 'Services publiés', $servicesUsed, $plan->max_services),
                $this->limitRow('pricing_sheets', 'Fiches tarifaires', $pricingSheetsUsed, $plan->max_pricing_sheets),
            ];
        }

        $profile = $subscription->providerProfile;
        $user = $profile?->user;

        $profilesUsed = $user !== null ? $this->profilesUsed($user) : 0;
        $servicesUsed = $profile !== null ? $this->servicesUsed($profile) : 0;
        $pricingSheetsUsed = $profile !== null ? $this->pricingSheetsUsed($profile) : 0;
        $mediaUsed = $profile !== null ? $profile->media()->count() : 0;

        return [
            $this->limitRow('profiles', 'Profils professionnels', $profilesUsed, $plan->max_profiles),
            $this->limitRow('services', 'Services publiés', $servicesUsed, $plan->max_services),
            $this->limitRow('pricing_sheets', 'Fiches tarifaires', $pricingSheetsUsed, $plan->max_pricing_sheets),
            $this->limitRow('media', 'Photos de galerie', $mediaUsed, $plan->max_media),
        ];
    }

    /**
     * Limites d'un plan pour un propriétaire sans abonnement (FREE).
     *
     * @return list<array<string, mixed>>
     */
    public function limitsForPlan(Plan $plan, int $profilesUsed = 0, int $servicesUsed = 0, int $pricingSheetsUsed = 0, int $mediaUsed = 0): array
    {
        return [
            $this->limitRow('profiles', 'Profils professionnels', $profilesUsed, $plan->max_profiles),
            $this->limitRow('services', 'Services publiés', $servicesUsed, $plan->max_services),
            $this->limitRow('pricing_sheets', 'Fiches tarifaires', $pricingSheetsUsed, $plan->max_pricing_sheets),
            $this->limitRow('media', 'Photos de galerie', $mediaUsed, $plan->max_media),
        ];
    }

    /**
     * Fonctionnalités d'un plan avec leur état.
     *
     * @return list<array{key: string, label: string, enabled: bool}>
     */
    public function featuresForPlan(Plan $plan): array
    {
        $features = [
            'advanced_stats' => 'Statistiques détaillées',
            'gallery' => 'Galerie photos',
            'collaborators' => 'Collaborateurs et permissions',
            'priority_visibility' => 'Mise en avant',
            'advanced_quotes' => 'Gestion avancée des devis',
            'bookings' => 'Gestion des réservations',
            'priority_support' => 'Support prioritaire',
            'highlight_options' => 'Options de visibilité',
            'profile_badge' => 'Badge de formule',
            'centralized_requests' => 'Demandes centralisées',
            'full_pricing_sheets' => 'Fiches tarifaires complètes',
            'simple_pricing_sheets' => 'Fiches tarifaires simples',
        ];

        return collect($features)
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'enabled' => $plan->hasFeature($key),
            ])
            ->values()
            ->all();
    }

    /**
     * Représentation d'un plan pour la page de comparaison.
     *
     * @return array<string, mixed>
     */
    public function planPayload(Plan $plan): array
    {
        return [
            'id' => $plan->id,
            'code' => $plan->code,
            'name' => $plan->name,
            'description' => $plan->description,
            'price_monthly' => (float) $plan->price_monthly,
            'price_yearly' => (float) $plan->price_yearly,
            'currency' => $plan->currency,
            'formatted_monthly' => $plan->formattedPriceFor('monthly'),
            'formatted_yearly' => $plan->formattedPriceFor('yearly'),
            'max_services' => $plan->max_services,
            'max_media' => $plan->max_media,
            'max_profiles' => $plan->max_profiles,
            'max_pricing_sheets' => $plan->max_pricing_sheets,
            'unlimited_services' => $plan->isUnlimitedServices(),
            'unlimited_pricing_sheets' => $plan->isUnlimitedPricingSheets(),
            'trial_days' => $plan->trial_days,
            'is_free' => $plan->isFree(),
            'features' => $this->featuresForPlan($plan),
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────

    /**
     * Construit une ligne de limite pour l'interface.
     *
     * @return array<string, mixed>
     */
    private function limitRow(string $key, string $label, int $used, int $max): array
    {
        $unlimited = $max === 0;

        return [
            'key' => $key,
            'label' => $label,
            'used' => $used,
            'limit' => $unlimited ? null : $max,
            'remaining' => $unlimited ? null : max(0, $max - $used),
            'unlimited' => $unlimited,
            'reached' => ! $unlimited && $used >= $max,
            'percentage' => $unlimited ? null : min(100, (int) round(($used / max(1, $max)) * 100)),
        ];
    }

    /**
     * Plan de secours si la base n'est pas encore seedée.
     */
    private function fallbackPlan(string $code): Plan
    {
        $attributes = match ($code) {
            'pro' => [
                'code' => 'pro',
                'name' => 'PRO',
                'price_monthly' => 5000,
                'price_yearly' => 50000,
                'max_services' => 20,
                'max_media' => 10,
                'max_profiles' => 1,
                'max_pricing_sheets' => 5,
                'has_advanced_stats' => true,
                'has_gallery' => true,
                'has_collaborators' => false,
                'has_priority_visibility' => false,
                'has_advanced_quotes' => true,
                'has_bookings' => true,
                'has_priority_support' => false,
                'has_highlight_options' => false,
                'has_profile_badge' => true,
                'has_centralized_requests' => false,
                'has_full_pricing_sheets' => true,
                'has_simple_pricing_sheets' => true,
            ],
            'business' => [
                'code' => 'business',
                'name' => 'BUSINESS',
                'price_monthly' => 10000,
                'price_yearly' => 100000,
                'max_services' => 50,
                'max_media' => 50,
                'max_profiles' => 5,
                'max_pricing_sheets' => 0,
                'has_advanced_stats' => true,
                'has_gallery' => true,
                'has_collaborators' => true,
                'has_priority_visibility' => true,
                'has_advanced_quotes' => true,
                'has_bookings' => true,
                'has_priority_support' => true,
                'has_highlight_options' => true,
                'has_profile_badge' => true,
                'has_centralized_requests' => true,
                'has_full_pricing_sheets' => true,
                'has_simple_pricing_sheets' => true,
            ],
            default => [
                'code' => 'free',
                'name' => 'FREE',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_services' => 3,
                'max_media' => 0,
                'max_profiles' => 1,
                'max_pricing_sheets' => 1,
                'has_advanced_stats' => false,
                'has_gallery' => false,
                'has_collaborators' => false,
                'has_priority_visibility' => false,
                'has_advanced_quotes' => false,
                'has_bookings' => false,
                'has_priority_support' => false,
                'has_highlight_options' => false,
                'has_profile_badge' => false,
                'has_centralized_requests' => false,
                'has_full_pricing_sheets' => false,
                'has_simple_pricing_sheets' => true,
            ],
        };

        return new Plan([...$attributes, 'currency' => 'XOF']);
    }
}
