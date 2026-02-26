<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_cost_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120)->default('Default');
            $table->decimal('packaging_cost', 18, 4)->default(0);
            $table->decimal('operational_cost', 18, 4)->default(0);
            $table->decimal('return_rate_default', 8, 4)->default(0);
            $table->decimal('ad_cost_default', 18, 4)->default(0);
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_cost_profiles');
    }
};

