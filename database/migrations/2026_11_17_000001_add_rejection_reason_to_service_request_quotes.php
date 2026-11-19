<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le motif de refus à la table des devis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_request_quotes', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_request_quotes', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
