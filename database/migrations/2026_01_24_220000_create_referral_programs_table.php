<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Webreen Tavsiye Programı');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('referrer_reward_type')->default('duration');
            $table->decimal('referrer_reward_value', 8, 2)->nullable();
            $table->string('referred_reward_type')->default('duration');
            $table->decimal('referred_reward_value', 8, 2)->nullable();
            $table->unsignedInteger('max_uses_per_referrer_per_year')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_programs');
    }
};
