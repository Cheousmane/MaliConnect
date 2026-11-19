<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // free, pro, business
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 12, 0)->default(0);
            $table->decimal('price_yearly', 12, 0)->default(0);
            $table->string('currency', 8)->default('XOF');
            $table->unsignedInteger('max_services')->default(0); // 0 = illimité
            $table->unsignedInteger('max_media')->default(0);
            $table->unsignedInteger('max_profiles')->default(1);
            $table->boolean('has_advanced_stats')->default(false);
            $table->boolean('has_gallery')->default(false);
            $table->boolean('has_collaborators')->default(false);
            $table->boolean('has_priority_visibility')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Lier les abonnements à un plan configurable.
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('provider_profile_id')->constrained('plans')->nullOnDelete();
            $table->string('billing_cycle')->default('monthly')->after('plan_id'); // monthly, yearly
            $table->timestamp('cancelled_at')->nullable()->after('ends_at');
            $table->timestamp('renewed_at')->nullable()->after('cancelled_at');
        });

        // Traçabilité des paiements externes et webhooks futurs.
        Schema::table('payments', function (Blueprint $table) {
            $table->string('external_reference')->nullable()->unique()->after('reference');
            $table->string('gateway')->nullable()->after('method'); // manual, orange_money, wave, ...
            $table->timestamp('verified_at')->nullable()->after('paid_at');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['external_reference', 'gateway', 'verified_at', 'verified_by']);
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['plan_id', 'billing_cycle', 'cancelled_at', 'renewed_at']);
        });
        Schema::dropIfExists('plans');
    }
};
