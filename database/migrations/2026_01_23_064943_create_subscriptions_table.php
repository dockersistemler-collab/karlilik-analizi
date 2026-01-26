<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('subscriptions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('plan_id')->constrained()->onDelete('cascade');
        $table->enum('status', ['active', 'cancelled', 'expired', 'suspended'])->default('active');
        $table->timestamp('starts_at');
        $table->timestamp('ends_at');
        $table->timestamp('cancelled_at')->nullable();
        $table->decimal('amount', 10, 2);
        $table->enum('billing_period', ['monthly', 'yearly']);
        $table->boolean('auto_renew')->default(true);
        
        // Kullanım istatistikleri (limitleri takip için)
        $table->integer('current_products_count')->default(0);
        $table->integer('current_marketplaces_count')->default(0);
        $table->integer('current_month_orders_count')->default(0);
        $table->timestamp('usage_reset_at')->nullable(); // Aylık kullanım resetleme
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
