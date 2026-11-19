<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des paiements.
 *
 * Seuls les paiements au statut « paid » alimentent les revenus affichés
 * dans le tableau de bord. Aucun montant n'est estimé ou simulé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();

            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_profile_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('amount', 12, 0);
            $table->string('currency', 3)->default('XOF');

            $table->string('method', 30)->default('mobile_money');
            $table->string('status', 20)->default('pending');

            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'paid_at']);
            $table->index(['provider_profile_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
