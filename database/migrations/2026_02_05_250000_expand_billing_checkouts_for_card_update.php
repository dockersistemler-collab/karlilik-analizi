<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_checkouts', function (Blueprint $table) {
            $table->string('purpose', 50)->default('plan_checkout')->after('plan_code');
            $table->uuid('billing_subscription_id')->nullable()->after('tenant_id');
            $table->json('raw_initialize')->nullable()->after('checkout_form_content');

            $table->index(['purpose', 'status']);
            $table->index(['billing_subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::table('billing_checkouts', function (Blueprint $table) {
            $table->dropIndex(['purpose', 'status']);
            $table->dropIndex(['billing_subscription_id']);
            $table->dropColumn(['purpose', 'billing_subscription_id', 'raw_initialize']);
        });
    }
};
