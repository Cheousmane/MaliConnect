<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rattache les services à un profil professionnel.
 *
 * La table `services` reste partagée avec l'ancien module `professionals` :
 * la colonne `professional_id` devient facultative et `provider_profile_id`
 * porte les profils du nouveau module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('provider_profile_id')->nullable()->after('professional_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_id')->nullable()->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->index(['provider_profile_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['provider_profile_id', 'is_active']);
            $table->dropConstrainedForeignId('provider_profile_id');
        });
    }
};
