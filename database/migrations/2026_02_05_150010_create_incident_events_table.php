<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('incident_id');
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('incident_id')->references('id')->on('incidents')->cascadeOnDelete();
            $table->index(['tenant_id', 'incident_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_events');
    }
};
