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
        Schema::create('core_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->string('marketplace');
            $table->string('order_id');
            $table->string('order_item_id');
            $table->timestamp('order_date');
            $table->timestamp('ship_date')->nullable();
            $table->timestamp('delivered_date')->nullable();

            $table->string('sku')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variant')->nullable();

            $table->unsignedInteger('quantity')->default(1);

            $table->string('currency', 3)->default('TRY');
            $table->decimal('fx_rate', 12, 6)->default(1);

            $table->decimal('gross_sales', 12, 2)->default(0);
            $table->decimal('discounts', 12, 2)->default(0);
            $table->decimal('refunds', 12, 2)->default(0);
            $table->decimal('net_sales', 12, 2)->default(0);

            $table->decimal('commission_fee', 12, 2)->default(0);
            $table->decimal('payment_fee', 12, 2)->default(0);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('other_fees', 12, 2)->default(0);
            $table->decimal('fees_total', 12, 2)->default(0);

            $table->decimal('vat_amount', 12, 2)->nullable();
            $table->decimal('tax_amount', 12, 2)->nullable();

            $table->decimal('cogs_unit', 12, 2)->nullable();
            $table->decimal('cogs_total', 12, 2)->nullable();

            $table->decimal('gross_profit', 12, 2)->nullable();
            $table->decimal('contribution_margin', 12, 2)->nullable();
            $table->decimal('net_profit', 12, 2)->nullable();

            $table->string('status')->default('paid');
            $table->timestamps();

            $table->unique(['tenant_id', 'marketplace', 'order_item_id'], 'core_order_items_unique');
            $table->index(['tenant_id', 'order_date']);
            $table->index(['tenant_id', 'marketplace']);
            $table->index(['tenant_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_order_items');
    }
};
