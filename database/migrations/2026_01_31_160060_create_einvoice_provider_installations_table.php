<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_invoice_provider_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider_key');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider_key']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_invoice_provider_installations');
    }
};
