<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_external_shocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('marketplace', 50)->index();
            $table->string('sku', 120)->nullable()->index();
            $table->date('date')->index();
            $table->string('shock_type', 30)->index();
            $table->string('severity', 20)->default('medium')->index();
            $table->string('detected_by', 20)->default('heuristic')->index();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'marketplace', 'date', 'shock_type'], 'marketplace_external_shocks_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_external_shocks');
    }
};

