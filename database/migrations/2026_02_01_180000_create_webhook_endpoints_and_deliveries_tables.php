<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('url', 2048);
            $table->text('secret');
            $table->json('events');

            $table->boolean('is_active')->default(true);
            $table->timestamp('disabled_at')->nullable();
            $table->string('disabled_reason', 255)->nullable();
            $table->timestamp('rotated_at')->nullable();
            $table->json('headers_json')->nullable();
            $table->unsignedTinyInteger('timeout_seconds')->default(10);

            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->uuid('delivery_uuid')->nullable();
            $table->string('event', 120);
            $table->json('payload_json');
            $table->json('payload_log_json')->nullable();
            $table->text('request_body')->nullable();
            $table->string('dedupe_key', 40)->nullable();

            $table->unsignedSmallInteger('attempt')->default(0);
            $table->enum('status', ['pending', 'success', 'failed', 'retrying', 'disabled'])->default('pending');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('response_body')->nullable();
            $table->json('response_headers_json')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->dateTime('next_retry_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('request_id', 64)->nullable();
            $table->json('request_headers_json')->nullable();
            $table->unsignedBigInteger('signature_timestamp')->nullable();
            $table->string('signature_v1', 64)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['webhook_endpoint_id', 'created_at']);
            $table->index(['status', 'next_retry_at']);
            $table->index(['request_id']);
            $table->index(['delivery_uuid']);
            $table->index(['dedupe_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
    }
};

