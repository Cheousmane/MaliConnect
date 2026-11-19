<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des abonnements professionnels.
 *
 * Un abonnement est rattaché à un profil professionnel. Les revenus ne sont
 * jamais déduits de cette table : ils proviennent exclusivement des paiements
 * réellement enregistrés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->string('plan', 30)->default('essential');
            $table->string('status', 20)->default('pending');

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->decimal('amount', 12, 0)->default(0);
            $table->string('currency', 3)->default('XOF');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['provider_profile_id', 'status']);
            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
