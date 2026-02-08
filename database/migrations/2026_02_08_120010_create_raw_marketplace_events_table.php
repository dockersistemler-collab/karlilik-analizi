<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('raw_marketplace_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->string('marketplace');
            $table->string('resource_type');
            $table->string('external_id');
            $table->json('payload');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('ingested_at')->useCurrent();
            $table->timestamps();

            $table->unique(['tenant_id', 'marketplace', 'resource_type', 'external_id'], 'raw_events_unique');
            $table->index(['tenant_id', 'marketplace', 'resource_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_marketplace_events');
    }
};
