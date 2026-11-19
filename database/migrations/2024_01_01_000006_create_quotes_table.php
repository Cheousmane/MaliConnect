<?php

use App\Models\Quote;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

            $table->string('reference')->unique();
            $table->text('message');
            $table->decimal('budget_min', 12, 0)->nullable();
            $table->decimal('budget_max', 12, 0)->nullable();
            $table->date('desired_date')->nullable();
            $table->string('address')->nullable();

            // Réponse du professionnel
            $table->string('status')->default(Quote::STATUS_PENDING);
            $table->text('professional_message')->nullable();
            $table->decimal('quoted_amount', 12, 0)->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['professional_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
