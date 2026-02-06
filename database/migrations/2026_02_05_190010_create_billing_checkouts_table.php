<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_checkouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id');
            $table->string('plan_code', 50);
            $table->string('status', 20)->default('pending');
            $table->string('provider')->nullable();
            $table->string('provider_session_id')->nullable();
            $table->text('checkout_form_content')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'plan_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_checkouts');
    }
};
