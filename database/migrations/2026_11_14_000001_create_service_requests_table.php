<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des demandes de devis adressées aux professionnels.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();

            // Client à l'origine de la demande.
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

            // Professionnel destinataire.
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('reference')->unique();
            $table->string('title');
            $table->text('description');

            // Localisation de l'intervention.
            $table->string('address')->nullable();
            $table->string('city')->default('Bamako');
            $table->string('commune')->nullable();
            $table->string('quartier')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Budget estimatif et délai souhaité.
            $table->decimal('budget_min', 12, 0)->nullable();
            $table->decimal('budget_max', 12, 0)->nullable();
            $table->date('desired_date')->nullable();
            $table->string('preferred_time', 20)->nullable();

            // Suivi.
            $table->string('status', 20)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['provider_profile_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['city', 'commune']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
