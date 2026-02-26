<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('marketplace', 50)->index();
            $table->date('date')->index();
            $table->decimal('risk_score', 8, 4)->default(0);
            $table->string('status', 20)->default('ok')->index();
            $table->json('reasons')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'marketplace', 'date'], 'marketplace_risk_scores_unique');
            $table->index(['tenant_id', 'user_id', 'marketplace', 'status'], 'marketplace_risk_scores_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_risk_scores');
    }
};

