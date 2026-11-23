<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Grille commerciale par défaut.
 *
 * Ces valeurs sont des hypothèses commerciales : elles restent modifiables
 * depuis l'administration sans redéploiement.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'free',
                'name' => 'FREE',
                'description' => 'Le socle pour démarrer : un profil, trois services et une fiche tarifaire simple.',
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
                'trial_days' => 0,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'pro',
                'name' => 'PRO',
                'description' => 'Pour les indépendants qui veulent convertir : 20 services, galerie, statistiques et badge Pro.',
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
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'business',
                'name' => 'BUSINESS',
                'description' => 'Pour les équipes : 5 profils, 50 services, collaborateurs, permissions, fiches multiples et mise en avant.',
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
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['code' => $plan['code']], $plan);
        }
    }
}
