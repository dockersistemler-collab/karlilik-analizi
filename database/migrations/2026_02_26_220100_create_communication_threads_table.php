<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained('marketplaces')->cascadeOnDelete();
            $table->foreignId('marketplace_store_id')->constrained('marketplace_stores')->cascadeOnDelete();
            $table->enum('channel', ['question', 'message', 'review', 'return']);
            $table->string('external_thread_id');
            $table->string('subject')->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_sku')->nullable();
            $table->string('product_name')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('external_order_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_external_id')->nullable();
            $table->enum('status', ['open', 'pending', 'answered', 'closed', 'overdue'])->default('open');
            $table->integer('priority_score')->default(0);
            $table->dateTime('due_at')->nullable();
            $table->dateTime('last_inbound_at')->nullable();
            $table->dateTime('last_outbound_at')->nullable();
            $table->integer('response_time_sec')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('external_thread_id');
            $table->unique(['marketplace_store_id', 'channel', 'external_thread_id'], 'comm_threads_store_channel_external_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_threads');
    }
};

