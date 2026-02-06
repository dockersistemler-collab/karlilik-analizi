<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace_code')->nullable();
            $table->string('provider_key')->nullable();
            $table->string('carrier_name_raw')->nullable();
            $table->string('carrier_name_normalized')->nullable();
            $table->string('tracking_number')->nullable();
            $table->enum('status', [
                'pending',
                'created',
                'in_transit',
                'delivered',
                'returned',
                'cancelled',
                'unmapped_carrier',
                'provider_not_installed',
                'error',
            ])->default('pending');
            $table->dateTime('last_event_at')->nullable();
            $table->dateTime('last_polled_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique('order_id');
            $table->index(['user_id', 'status']);
            $table->index(['provider_key', 'status']);
        });

        Schema::create('shipment_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('provider_key')->nullable();
            $table->string('event_code')->nullable();
            $table->string('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('occurred_at')->nullable();
            $table->json('payload_json')->nullable();
            $table->string('hash')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['shipment_id', 'hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_tracking_events');
        Schema::dropIfExists('shipments');
    }
};
