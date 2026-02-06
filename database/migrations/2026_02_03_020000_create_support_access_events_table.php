<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_access_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_access_log_id')->nullable()->constrained('support_access_logs')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('method');
            $table->string('route_name')->nullable();
            $table->text('url');
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['support_access_log_id', 'created_at'], 'support_access_events_log_created_idx');
            $table->index(['actor_user_id', 'created_at'], 'support_access_events_actor_created_idx');
            $table->index(['target_user_id', 'created_at'], 'support_access_events_target_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_access_events');
    }
};
