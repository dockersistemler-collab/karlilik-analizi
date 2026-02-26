<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_profit_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('marketplace', 50)->nullable()->index();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('gross_revenue', 18, 4)->default(0);
            $table->decimal('product_cost', 18, 4)->default(0);
            $table->decimal('commission_amount', 18, 4)->default(0);
            $table->decimal('shipping_amount', 18, 4)->default(0);
            $table->decimal('service_amount', 18, 4)->default(0);
            $table->decimal('campaign_amount', 18, 4)->default(0);
            $table->decimal('ad_amount', 18, 4)->default(0);
            $table->decimal('packaging_amount', 18, 4)->default(0);
            $table->decimal('operational_amount', 18, 4)->default(0);
            $table->decimal('return_risk_amount', 18, 4)->default(0);
            $table->decimal('other_cost_amount', 18, 4)->default(0);
            $table->decimal('net_profit', 18, 4)->default(0);
            $table->decimal('net_margin', 8, 4)->default(0);
            $table->string('calculation_version', 32)->default('v1');
            $table->dateTime('calculated_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['order_id'], 'order_profit_snapshots_order_unique');
            $table->index(['tenant_id', 'user_id', 'marketplace']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_profit_snapshots');
    }
};

