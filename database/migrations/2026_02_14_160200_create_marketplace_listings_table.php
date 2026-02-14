<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('marketplace_account_id')->constrained('marketplace_accounts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('external_listing_id')->nullable();
            $table->string('external_sku')->nullable();
            $table->string('external_barcode')->nullable();
            $table->boolean('sync_enabled')->default(true);
            $table->integer('last_known_market_stock')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'marketplace_account_id']);
            $table->index(['tenant_id', 'external_sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};
