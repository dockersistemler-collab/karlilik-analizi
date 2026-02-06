<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('audience_role')->nullable();
            $table->string('marketplace')->nullable();
            $table->string('source')->nullable();
            $table->enum('type', ['critical', 'operational', 'info']);
            $table->enum('channel', ['in_app', 'email']);
            $table->string('title', 190);
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('action_url')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->string('group_key')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'read_at']);
            $table->index(['tenant_id', 'type', 'created_at']);
            $table->index(['tenant_id', 'marketplace', 'created_at']);
            $table->index(['tenant_id', 'dedupe_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};