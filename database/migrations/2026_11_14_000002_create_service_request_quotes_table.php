<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des devis envoyés en réponse aux demandes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_request_quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();

            $table->string('reference')->unique();
            $table->text('message');
            $table->decimal('amount', 12, 0);
            $table->unsignedSmallInteger('duration_minutes')->nullable();

            // Validité du devis et statut de la réponse du client.
            $table->string('status', 20)->default('pending');
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            $table->index(['service_request_id', 'status']);
            $table->index(['provider_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_quotes');
    }
};
