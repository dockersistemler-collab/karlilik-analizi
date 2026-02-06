<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_checkouts', function (Blueprint $table) {
            $table->string('provider_token')->nullable()->after('provider_session_id');
            $table->json('raw_callback')->nullable()->after('checkout_form_content');
            $table->json('raw_webhook')->nullable()->after('raw_callback');
            $table->timestamp('completed_at')->nullable()->after('raw_webhook');

            $table->unique('provider_token');
        });
    }

    public function down(): void
    {
        Schema::table('billing_checkouts', function (Blueprint $table) {
            $table->dropUnique(['provider_token']);
            $table->dropColumn(['provider_token', 'raw_callback', 'raw_webhook', 'completed_at']);
        });
    }
};
