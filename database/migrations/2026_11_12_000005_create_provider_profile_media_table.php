<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des médias associés à un profil professionnel
 * (photos, réalisations, couverture).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_profile_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            // Type de média : photo, réalisation ou couverture.
            $table->string('type', 20)->default('realisation');
            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('position')->default(0);

            $table->timestamps();

            $table->index(['provider_profile_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_profile_media');
    }
};
