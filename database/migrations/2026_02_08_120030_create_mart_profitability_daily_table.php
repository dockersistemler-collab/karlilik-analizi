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
        Schema::create('mart_profitability_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('marketplace');

            $table->decimal('net_sales', 12, 2)->default(0);
            $table->decimal('fees_total', 12, 2)->default(0);
            $table->decimal('cogs_total', 12, 2)->default(0);
            $table->decimal('gross_profit', 12, 2)->default(0);
            $table->decimal('contribution_margin', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->nullable();
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('items_count')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'date', 'marketplace'], 'mart_profitability_daily_unique');
            $table->index(['tenant_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mart_profitability_daily');
    }
};
