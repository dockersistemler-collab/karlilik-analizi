<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->decimal('referrer_discount_amount', 10, 2)->nullable();
            $table->timestamp('referrer_discount_consumed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn(['referrer_discount_amount', 'referrer_discount_consumed_at']);
        });
    }
};
