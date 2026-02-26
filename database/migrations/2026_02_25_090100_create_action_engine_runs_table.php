<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_engine_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('run_date')->index();
            $table->json('stats')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'run_date'], 'action_engine_runs_tenant_date_unique');
            $table->index(['tenant_id', 'user_id', 'run_date'], 'action_engine_runs_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_engine_runs');
    }
};

