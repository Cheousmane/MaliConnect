<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des collaborateurs d'entreprise.
 *
 * Un utilisateur ne peut occuper qu'une seule place par entreprise, ce qui
 * garantit la cohérence des rôles lors des affectations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Rôle du collaborateur dans l'entreprise (responsable, gestionnaire, collaborateur).
            $table->string('role', 20)->default('agent');

            // État de la collaboration (active, invited, suspended).
            $table->string('status', 20)->default('active');

            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_members');
    }
};
