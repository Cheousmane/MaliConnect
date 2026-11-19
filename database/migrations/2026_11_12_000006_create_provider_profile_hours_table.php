<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des horaires d'ouverture d'un profil professionnel.
 *
 * Un profil ne dispose que d'une ligne par jour de la semaine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_profile_hours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            // Jour de la semaine (0 = dimanche, 6 = samedi).
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);

            $table->timestamps();

            $table->unique(['provider_profile_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_profile_hours');
    }
};
