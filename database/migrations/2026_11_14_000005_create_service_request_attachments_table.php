<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des pièces jointes (photos et documents) d'une demande.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_request_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();

            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('name');
            $table->string('type', 20)->default('photo');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->timestamps();

            $table->index(['service_request_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_attachments');
    }
};
