<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des signalements.
 *
 * Un signalement vise n'importe quelle ressource modélisée (profil,
 * entreprise, service, avis, compte). La modération est réservée à
 * l'administration et laisse toujours une trace dans le journal d'audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();

            $table->morphs('reportable');

            $table->string('reason', 50);
            $table->text('description')->nullable();

            $table->string('status', 20)->default('pending');

            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('resolution_note')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
