<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Étend les abonnements au modèle SaaS multi-propriétaire.
 *
 * Un abonnement courant appartient soit à un profil professionnel
 * indépendant, soit à une entreprise. L'historique des changements est
 * conservé dans `subscription_histories`, ce qui évite de dupliquer les
 * abonnements pour chaque événement du cycle de vie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Un abonnement d'entreprise n'est rattaché à aucun profil précis.
            $table->foreignId('provider_profile_id')->nullable()->change();

            $table->foreignId('business_id')->nullable()->after('provider_profile_id')->constrained()->cascadeOnDelete();

            // Changement de formule programmé (déclassement vers FREE notamment).
            $table->foreignId('pending_plan_id')->nullable()->after('plan_id')->constrained('plans')->nullOnDelete();
            $table->string('pending_billing_cycle', 20)->nullable()->after('pending_plan_id');
            $table->timestamp('pending_plan_effective_at')->nullable()->after('pending_billing_cycle');

            // Cycle de vie.
            $table->timestamp('activated_at')->nullable()->after('ends_at');
            $table->timestamp('trial_ends_at')->nullable()->after('activated_at');
            $table->timestamp('grace_ends_at')->nullable()->after('trial_ends_at');
            $table->timestamp('suspended_at')->nullable()->after('grace_ends_at');
            $table->boolean('cancel_at_period_end')->default(false)->after('cancelled_at');
            $table->text('cancellation_reason')->nullable()->after('cancel_at_period_end');
            $table->timestamp('last_payment_at')->nullable()->after('renewed_at');

            // Suivi des notifications automatiques pour éviter les doublons.
            $table->timestamp('expiry_reminder_sent_at')->nullable()->after('last_payment_at');
            $table->timestamp('past_due_notified_at')->nullable()->after('expiry_reminder_sent_at');

            $table->index(['business_id', 'status']);
            $table->index(['pending_plan_effective_at']);
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'status']);
            $table->dropIndex(['pending_plan_effective_at']);

            $table->dropConstrainedForeignId('pending_plan_id');
            $table->dropConstrainedForeignId('business_id');

            $table->dropColumn([
                'pending_billing_cycle',
                'pending_plan_effective_at',
                'activated_at',
                'trial_ends_at',
                'grace_ends_at',
                'suspended_at',
                'cancel_at_period_end',
                'cancellation_reason',
                'last_payment_at',
                'expiry_reminder_sent_at',
                'past_due_notified_at',
            ]);

            $table->foreignId('provider_profile_id')->nullable(false)->change();
        });
    }
};
