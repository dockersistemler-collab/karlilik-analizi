<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_risk_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('marketplace', 50)->index();
            $table->string('name', 120)->default('Default');
            $table->json('weights');
            $table->json('thresholds');
            $table->json('metric_thresholds');
            $table->boolean('is_default')->default(true)->index();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'marketplace'], 'marketplace_risk_profiles_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_risk_profiles');
    }
};
