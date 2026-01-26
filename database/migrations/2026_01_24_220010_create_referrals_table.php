<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('referred_email')->nullable();
            $table->foreignId('program_id')->nullable()->constrained('referral_programs')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('referrer_reward_type')->nullable();
            $table->decimal('referrer_reward_value', 8, 2)->nullable();
            $table->string('referred_reward_type')->nullable();
            $table->decimal('referred_reward_value', 8, 2)->nullable();
            $table->decimal('applied_discount_amount', 10, 2)->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
            $table->index(['referred_email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
