<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_tower_daily_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->date('date');
            $table->json('payload');
            $table->timestamps();

            $table->unique(['tenant_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });

        Schema::create('control_tower_signals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->date('date');
            $table->string('scope', 32)->default('global');
            $table->string('marketplace', 64)->nullable();
            $table->string('sku', 191)->nullable();
            $table->string('severity', 16);
            $table->string('type', 64);
            $table->string('title');
            $table->text('message');
            $table->json('drivers')->nullable();
            $table->json('action_hint')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'date', 'severity', 'type'], 'ct_signals_tenant_date_severity_type');
            $table->index(['tenant_id', 'date', 'type', 'marketplace', 'sku'], 'ct_signals_upsert_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_tower_signals');
        Schema::dropIfExists('control_tower_daily_snapshots');
    }
};

