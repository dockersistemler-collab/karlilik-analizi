<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_event_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billing_event_id')->index();
            $table->string('action_type', 50)->index();
            $table->unsignedBigInteger('requested_by_admin_id')->index();
            $table->string('status', 20)->index();
            $table->text('error_message')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_event_actions');
    }
};
