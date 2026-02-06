<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_subscription_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id')->index();
            $table->string('provider_event_id')->nullable();
            $table->string('event_type', 50);
            $table->string('event_hash', 64)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->unique('provider_event_id');
            $table->unique('event_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_subscription_events');
    }
};
