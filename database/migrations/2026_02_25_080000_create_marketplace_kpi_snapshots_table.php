<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('marketplace', 50)->index();
            $table->date('date')->index();
            $table->decimal('late_shipment_rate', 8, 4)->nullable();
            $table->decimal('cancellation_rate', 8, 4)->nullable();
            $table->decimal('return_rate', 8, 4)->nullable();
            $table->decimal('performance_score', 8, 4)->nullable();
            $table->decimal('rating_score', 8, 4)->nullable();
            $table->decimal('odr', 8, 4)->nullable();
            $table->decimal('valid_tracking_rate', 8, 4)->nullable();
            $table->string('source', 40)->default('manual');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'marketplace', 'date'], 'marketplace_kpi_snapshots_unique');
            $table->index(['tenant_id', 'user_id', 'marketplace', 'date'], 'marketplace_kpi_snapshots_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_kpi_snapshots');
    }
};

