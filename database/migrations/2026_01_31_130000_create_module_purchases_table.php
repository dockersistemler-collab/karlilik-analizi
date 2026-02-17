<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->enum('provider', ['iyzico', 'manual', 'fake']);
            $table->string('provider_payment_id')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('TRY');
            $table->enum('period', ['monthly', 'yearly', 'one_time'])->default('monthly');
            $table->enum('status', ['pending', 'paid', 'cancelled', 'refunded'])->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_payment_id']);
            $table->index(['status', 'provider']);
            $table->index(['user_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_purchases');
    }
};

