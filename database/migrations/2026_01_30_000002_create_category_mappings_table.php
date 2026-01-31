<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();

            $table->foreignId('marketplace_category_id')
                ->nullable()
                ->constrained('marketplace_categories')
                ->nullOnDelete();
            $table->string('marketplace_category_external_id');

            $table->string('source')->default('manual'); // manual|import|ai
            $table->unsignedTinyInteger('confidence')->nullable(); // 0-100
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'marketplace_id'], 'cat_map_user_cat_market_unique');
            $table->unique(
                ['user_id', 'marketplace_id', 'marketplace_category_external_id'],
                'cat_map_user_market_ext_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_mappings');
    }
};

