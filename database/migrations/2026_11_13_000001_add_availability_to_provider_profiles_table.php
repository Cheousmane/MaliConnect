<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la disponibilité immédiate du profil et les index de recherche.
 *
 * Les index couvrent les filtres les plus fréquents de la recherche publique :
 * statut et activité, note, localisation et classement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            // Disponibilité immédiate du professionnel (créneaux dans la fiche).
            $table->boolean('is_available')->default(true)->after('is_active');

            $table->index(['status', 'is_active', 'is_available']);
            $table->index(['city', 'commune']);
            $table->index(['city', 'commune', 'quartier']);
            $table->index(['category_id', 'subcategory_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_active', 'is_available']);
            $table->dropIndex(['city', 'commune']);
            $table->dropIndex(['city', 'commune', 'quartier']);
            $table->dropIndex(['category_id', 'subcategory_id', 'status']);

            $table->dropColumn('is_available');
        });
    }
};
