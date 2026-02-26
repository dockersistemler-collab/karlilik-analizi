<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_offer_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('marketplace', 50)->index();
            $table->date('date')->index();
            $table->string('sku', 191)->index();
            $table->string('listing_id', 191)->nullable();
            $table->boolean('is_winning')->default(false);
            $table->integer('position_rank')->nullable();
            $table->decimal('our_price', 12, 4)->nullable();
            $table->decimal('competitor_best_price', 12, 4)->nullable();
            $table->integer('competitor_count')->nullable();
            $table->decimal('shipping_speed_score', 8, 4)->nullable();
            $table->integer('stock_available')->nullable();
            $table->decimal('store_score', 8, 4)->nullable();
            $table->decimal('rating_score', 8, 4)->nullable();
            $table->boolean('promo_flag')->default(false);
            $table->json('meta')->nullable();
            $table->string('source', 40)->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'marketplace', 'date', 'sku'],
                'marketplace_offer_snapshots_unique'
            );
        });

        Schema::create('marketplace_competitor_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('snapshot_id');
            $table->string('seller_name', 191)->nullable();
            $table->decimal('price', 12, 4)->nullable();
            $table->string('shipping_speed', 120)->nullable();
            $table->decimal('store_score', 8, 4)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('snapshot_id')
                ->references('id')
                ->on('marketplace_offer_snapshots')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_competitor_offers');
        Schema::dropIfExists('marketplace_offer_snapshots');
    }
};

