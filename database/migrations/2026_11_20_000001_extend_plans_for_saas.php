<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complète les formules avec les limites et fonctionnalités SaaS.
 *
 * Les colonnes de la table `plans` constituent le système de fonctionnalités
 * configurables : chaque limite et chaque option est modifiable depuis
 * l'administration sans redéploiement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_pricing_sheets')->default(1);
            $table->boolean('has_advanced_quotes')->default(false);
            $table->boolean('has_bookings')->default(false);
            $table->boolean('has_priority_support')->default(false);
            $table->boolean('has_highlight_options')->default(false);
            $table->boolean('has_profile_badge')->default(false);
            $table->boolean('has_centralized_requests')->default(false);
            $table->boolean('has_full_pricing_sheets')->default(false);
            $table->boolean('has_simple_pricing_sheets')->default(true);
            $table->unsignedSmallInteger('trial_days')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'max_pricing_sheets',
                'has_advanced_quotes',
                'has_bookings',
                'has_priority_support',
                'has_highlight_options',
                'has_profile_badge',
                'has_centralized_requests',
                'has_full_pricing_sheets',
                'has_simple_pricing_sheets',
                'trial_days',
            ]);
        });
    }
};
