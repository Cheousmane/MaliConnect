<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des disponibilités ponctuelles d'un profil professionnel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_profile_availabilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_available')->default(true);
            $table->string('label')->nullable();

            $table->timestamps();

            $table->unique(['provider_profile_id', 'date', 'starts_at']);
            $table->index(['provider_profile_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_profile_availabilities');
    }
};
