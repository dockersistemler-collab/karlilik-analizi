<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('marketplace', 50)->index();
            $table->string('sku', 120)->index();
            $table->date('date')->index();
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->unsignedInteger('units_sold')->default(0);
            $table->decimal('revenue', 18, 4)->default(0);
            $table->boolean('is_promo_day')->default(false)->index();
            $table->boolean('is_shipping_shock')->default(false)->index();
            $table->boolean('is_fee_shock')->default(false)->index();
            $table->json('shock_flags')->nullable();
            $table->string('promo_source', 20)->nullable();
            $table->string('promo_campaign_id', 80)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'marketplace', 'sku', 'date'], 'marketplace_price_history_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_price_history');
    }
};

