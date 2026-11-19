<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des zones d'intervention d'un profil professionnel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_areas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->string('city')->default('Bamako');
            $table->string('commune')->nullable();
            $table->string('quartier')->nullable();
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->index(['provider_profile_id']);
            $table->index(['city', 'commune']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_areas');
    }
};
