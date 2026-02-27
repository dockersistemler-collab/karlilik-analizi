<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('communication_threads')->cascadeOnDelete();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->text('body');
            $table->dateTime('created_at_external')->nullable();
            $table->enum('sender_type', ['customer', 'seller', 'system'])->default('customer');
            $table->boolean('ai_suggested')->default(false);
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['thread_id', 'direction', 'created_at_external'], 'comm_messages_thread_dir_ext_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_messages');
    }
};

