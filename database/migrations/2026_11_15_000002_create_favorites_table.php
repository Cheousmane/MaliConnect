<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des professionnels mis en favori par les utilisateurs.
 *
 * La contrainte d'unicité (user_id, provider_profile_id) garantit qu'un même
 * professionnel ne peut figurer qu'une seule fois dans une liste de favoris.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'provider_profile_id']);
            $table->index(['provider_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
