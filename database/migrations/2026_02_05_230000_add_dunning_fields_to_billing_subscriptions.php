<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_subscriptions', function (Blueprint $table) {
            $table->timestamp('past_due_since')->nullable()->after('next_payment_at');
            $table->timestamp('grace_until')->nullable()->after('past_due_since');
            $table->timestamp('last_dunning_sent_at')->nullable()->after('grace_until');
            $table->index('grace_until');
        });
    }

    public function down(): void
    {
        Schema::table('billing_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['grace_until']);
            $table->dropColumn(['past_due_since', 'grace_until', 'last_dunning_sent_at']);
        });
    }
};
