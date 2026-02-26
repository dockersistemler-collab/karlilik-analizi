<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('marketplace', 50)->index();
            $table->string('sku', 120)->default('')->index();
            $table->string('severity', 20)->default('medium')->index();
            $table->string('title', 190);
            $table->text('description');
            $table->string('action_type', 40)->index();
            $table->json('suggested_payload')->nullable();
            $table->json('reason')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->dateTime('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'date', 'action_type', 'marketplace', 'sku'],
                'action_recommendations_dedupe_unique'
            );
            $table->index(['tenant_id', 'user_id', 'status', 'date'], 'action_recommendations_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_recommendations');
    }
};

