<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            if (!Schema::hasColumn('referrals', 'referrer_reward_type')) {
                $table->string('referrer_reward_type')->nullable();
            }
            if (!Schema::hasColumn('referrals', 'referrer_reward_value')) {
                $table->decimal('referrer_reward_value', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('referrals', 'referred_reward_type')) {
                $table->string('referred_reward_type')->nullable();
            }
            if (!Schema::hasColumn('referrals', 'referred_reward_value')) {
                $table->decimal('referred_reward_value', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('referrals', 'applied_discount_amount')) {
                $table->decimal('applied_discount_amount', 10, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn([
                'referrer_reward_type',
                'referrer_reward_value',
                'referred_reward_type',
                'referred_reward_value',
                'applied_discount_amount',
            ]);
        });
    }
};
