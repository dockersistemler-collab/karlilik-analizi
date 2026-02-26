<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_engine_calibrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('marketplace', 50)->nullable()->index();
            $table->string('sku', 120)->nullable()->index();
            $table->unsignedInteger('window_days')->default(45);
            $table->decimal('elasticity', 12, 6)->default(0);
            $table->decimal('margin_uplift_factor', 12, 6)->default(1);
            $table->decimal('ad_pause_revenue_drop_pct', 8, 4)->default(0);
            $table->decimal('confidence', 8, 4)->default(0);
            $table->json('diagnostics')->nullable();
            $table->dateTime('calculated_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'marketplace', 'sku'], 'action_engine_calibrations_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_engine_calibrations');
    }
};

