<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('parent_external_id')->nullable();
            $table->string('name');
            $table->string('path')->nullable();
            $table->boolean('is_leaf')->default(false);
            $table->json('raw')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'marketplace_id', 'external_id'], 'mp_cat_user_market_ext_unique');
            $table->index(['user_id', 'marketplace_id', 'name'], 'mp_cat_user_market_name_idx');
            $table->index(['user_id', 'marketplace_id', 'is_leaf'], 'mp_cat_user_market_leaf_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_categories');
    }
};

