<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des profils professionnels.
 *
 * Un profil peut être rattaché à une entreprise et reste la propriété du
 * compte qui l'a créé. Sa visibilité publique est conditionnée par la
 * vérification administrative.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();

            // Compte propriétaire du profil.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Entreprise éventuelle portant le profil.
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();

            // Classement du métier.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();

            // Identité professionnelle.
            $table->string('business_name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();

            // Coordonnées.
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();

            // Localisation.
            $table->string('address')->nullable();
            $table->string('city')->default('Bamako');
            $table->string('commune')->nullable();
            $table->string('quartier')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Réputation et activité.
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->unsignedSmallInteger('response_time_hours')->nullable();
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            // Vérification administrative.
            $table->string('status', 20)->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('refusal_reason')->nullable();

            // État de publication.
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            // Abonnement.
            $table->string('subscription_plan', 20)->default('free');
            $table->timestamp('subscription_expires_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'is_active']);
            $table->index(['business_id', 'status']);
            $table->index(['category_id', 'city', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};
