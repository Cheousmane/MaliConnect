<?php

use App\Models\Professional;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();

            // Identité professionnelle
            $table->string('business_name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();

            // Contact
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();

            // Localisation
            $table->string('address')->nullable();
            $table->string('city')->default('Bamako');
            $table->string('commune')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Médias
            $table->string('avatar')->nullable();
            $table->string('cover_image')->nullable();

            // Infos pro
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->unsignedSmallInteger('response_time_hours')->nullable();

            // Statut
            $table->string('status')->default(Professional::STATUS_PENDING);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            // Évaluations (mis à jour via événements)
            $table->decimal('rating_average', 3, 2)->default(0.00);
            $table->unsignedInteger('rating_count')->default(0);

            // Abonnement SaaS
            $table->string('subscription_plan')->default(Professional::PLAN_FREE);
            $table->timestamp('subscription_expires_at')->nullable();

            $table->timestamps();

            // Index pour les recherches courantes
            $table->index(['status', 'is_active', 'is_featured']);
            $table->index(['category_id', 'city', 'status']);
            $table->index('rating_average');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professionals');
    }
};
