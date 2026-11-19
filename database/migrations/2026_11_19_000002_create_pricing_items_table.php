<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée les prestations tarifées d'une fiche.
 *
 * Une prestation peut être reliée à un service existant du profil afin
 * d'éviter toute duplication de données. Les montants sont exprimés en
 * unités entières de la devise de la fiche (FCFA par défaut).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pricing_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Mode de tarification : forfait, heure, journée, intervention, devis.
            $table->string('pricing_type', 20)->default('quote');
            $table->decimal('price', 12, 0)->nullable();
            $table->decimal('price_min', 12, 0)->nullable();
            $table->decimal('price_max', 12, 0)->nullable();
            $table->string('billing_unit', 50)->nullable();
            $table->text('conditions')->nullable();
            $table->unsignedSmallInteger('estimated_duration_minutes')->nullable();

            $table->boolean('is_available')->default(true);
            $table->boolean('requires_diagnostic')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['pricing_sheet_id', 'sort_order']);
            $table->index(['service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_items');
    }
};
