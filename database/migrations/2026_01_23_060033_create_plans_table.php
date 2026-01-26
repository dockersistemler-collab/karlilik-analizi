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
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('yearly_price', 10, 2)->nullable();
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');
            
            $table->integer('max_products')->default(0);
            $table->integer('max_marketplaces')->default(0);
            $table->integer('max_orders_per_month')->default(0);
            $table->boolean('api_access')->default(false);
            $table->boolean('advanced_reports')->default(false);
            $table->boolean('priority_support')->default(false);
            $table->boolean('custom_integrations')->default(false);
            
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};