<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Étend les paiements au parcours de souscription SaaS.
 *
 * Un paiement peut viser une entreprise ou un profil indépendant, et
 * précise la formule et la périodicité déclarées. Les preuves sont stockées
 * séparément sur un disque privé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->after('subscription_id')->constrained('plans')->nullOnDelete();
            $table->string('billing_cycle', 20)->default('monthly')->after('plan_id');

            $table->timestamp('declared_at')->nullable()->after('paid_at');
            $table->text('rejection_reason')->nullable()->after('notes');

            $table->index(['business_id', 'status']);
            $table->index(['plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status']);
            $table->dropIndex(['plan_id', 'status']);

            $table->dropConstrainedForeignId('plan_id');
            $table->dropConstrainedForeignId('business_id');

            $table->dropColumn(['billing_cycle', 'declared_at', 'rejection_reason']);
        });
    }
};
