<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan_code', 50)->default('free')->index()->after('role');
            $table->timestamp('plan_started_at')->nullable()->after('plan_code');
            $table->timestamp('plan_expires_at')->nullable()->after('plan_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plan_code', 'plan_started_at', 'plan_expires_at']);
        });
    }
};
