<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_fee_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('marketplace', 50)->index();
            $table->string('sku', 120)->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('brand_id')->nullable()->index();
            $table->decimal('commission_rate', 8, 4)->default(0);
            $table->decimal('fixed_fee', 18, 4)->default(0);
            $table->decimal('shipping_fee', 18, 4)->default(0);
            $table->decimal('service_fee', 18, 4)->default(0);
            $table->decimal('campaign_contribution_rate', 8, 4)->default(0);
            $table->decimal('vat_rate', 8, 4)->default(0);
            $table->unsignedInteger('priority')->default(0)->index();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'marketplace', 'active'], 'fee_rules_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_fee_rules');
    }
};

