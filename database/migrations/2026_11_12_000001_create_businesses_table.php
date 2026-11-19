<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des entreprises : une entreprise peut porter plusieurs
 * profils professionnels et plusieurs collaborateurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();

            // Compte à l'origine de la fiche (le propriétaire est aussi membre).
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->text('description')->nullable();

            // Coordonnées
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->default('Bamako');
            $table->string('commune')->nullable();
            $table->string('logo_path')->nullable();

            // Vérification administrative
            $table->string('status', 20)->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('refusal_reason')->nullable();

            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index(['status', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
