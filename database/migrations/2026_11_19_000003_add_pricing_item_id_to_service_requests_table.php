<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rattache une demande de devis à la prestation tarifée d'origine.
 *
 * Le lien reste facultatif : les demandes créées librement continuent de
 * fonctionner sans fiche tarifaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('pricing_item_id')
                ->nullable()
                ->after('service_id')
                ->constrained('pricing_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pricing_item_id');
        });
    }
};
