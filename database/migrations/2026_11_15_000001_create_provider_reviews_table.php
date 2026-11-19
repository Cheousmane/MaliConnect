<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des avis liés aux prestations réellement terminées.
 *
 * Un avis est rattaché à une réservation unique : la contrainte d'unicité
 * empêche qu'une même prestation soit notée plusieurs fois. Seuls les avis
 * approuvés par l'administration alimentent la note publique du profil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_reviews', function (Blueprint $table) {
            $table->id();

            // Réservation justifiant l'avis : une seule évaluation par prestation.
            $table->foreignId('provider_booking_id')->unique()->constrained()->cascadeOnDelete();

            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();

            // Modération : pending, approved, rejected.
            $table->string('status', 20)->default('pending');
            $table->timestamp('published_at')->nullable();

            // Réponse publique du professionnel.
            $table->text('professional_reply')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            $table->index(['provider_profile_id', 'status', 'published_at']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_reviews');
    }
};
