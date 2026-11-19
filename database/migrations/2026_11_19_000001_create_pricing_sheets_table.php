<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée les fiches tarifaires des profils professionnels.
 *
 * Une fiche appartient à un profil public. Le jeton public permet de partager
 * un lien stable sans exposer l'identifiant interne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_sheets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('public_token', 36)->unique();
            $table->text('description')->nullable();
            $table->string('currency', 10)->default('XOF');
            $table->text('general_conditions')->nullable();

            // Cycle de vie : brouillon, publiée ou archivée.
            $table->string('status', 20)->default('draft');
            $table->date('valid_until')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            $table->index(['provider_profile_id', 'status']);
            $table->index(['status', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_sheets');
    }
};
