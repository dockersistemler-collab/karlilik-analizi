<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('provider', 50)->default('iyzico');
            $table->string('plan_code', 50);
            $table->string('status', 30)->nullable();
            $table->string('iyzico_subscription_reference_code')->nullable()->unique();
            $table->string('iyzico_customer_reference_code')->nullable();
            $table->string('iyzico_pricing_plan_reference_code')->nullable();
            $table->string('iyzico_checkout_form_token')->nullable()->unique();
            $table->text('iyzico_checkout_form_content')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamp('next_payment_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'plan_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_subscriptions');
    }
};
