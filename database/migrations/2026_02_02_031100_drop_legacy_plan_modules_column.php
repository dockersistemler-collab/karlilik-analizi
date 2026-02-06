<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!config('app.drop_legacy_columns', false)) {
            return;
        }

        if (!Schema::hasColumn('plans', 'plan_modules')) {
            return;
        }

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('plan_modules');
        });
    }

    public function down(): void
    {
        // no-op: legacy column removal is intentional
    }
};
