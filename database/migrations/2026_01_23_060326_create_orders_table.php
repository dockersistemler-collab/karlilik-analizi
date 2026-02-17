<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('marketplace_order_id')->unique();
            $table->string('order_number')->nullable();
            $table->string('status');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('commission_amount', 10, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->nullable();
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
            $table->json('items')->nullable();
            $table->json('marketplace_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
