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
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');
        $table->string('marketplace_order_id')->unique(); // Pazaryerindeki sipariş numarası
        $table->string('order_number')->nullable(); // Kendi sipariş numaramız
        $table->string('status'); // pending, approved, shipped, delivered, cancelled, returned
        $table->decimal('total_amount', 10, 2);
        $table->decimal('commission_amount', 10, 2)->nullable();
        $table->decimal('net_amount', 10, 2)->nullable(); // Komisyon sonrası
        $table->string('currency')->default('TRY');
        $table->string('customer_name');
        $table->string('customer_email')->nullable();
        $table->string('customer_phone')->nullable();
        $table->text('shipping_address')->nullable();
        $table->text('billing_address')->nullable();
        $table->string('cargo_company')->nullable();
        $table->string('tracking_number')->nullable();
        $table->timestamp('order_date');
        $table->timestamp('approved_at')->nullable();
        $table->timestamp('shipped_at')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->json('items')->nullable(); // Sipariş kalemleri
        $table->json('marketplace_data')->nullable(); // Pazaryerine özel data
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
