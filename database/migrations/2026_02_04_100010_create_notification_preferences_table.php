<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['critical', 'operational', 'info']);
            $table->enum('channel', ['in_app', 'email']);
            $table->string('marketplace')->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('quiet_hours')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'type', 'channel', 'marketplace'], 'notification_pref_unique');
            $table->index(['tenant_id', 'user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};