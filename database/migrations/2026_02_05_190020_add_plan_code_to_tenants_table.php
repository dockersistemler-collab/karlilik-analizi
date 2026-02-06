<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'plan_code')) {
                $table->string('plan_code', 50)->default('free')->index();
            }
            if (!Schema::hasColumn('tenants', 'plan_started_at')) {
                $table->timestamp('plan_started_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'plan_code')) {
                $table->dropColumn('plan_code');
            }
            if (Schema::hasColumn('tenants', 'plan_started_at')) {
                $table->dropColumn('plan_started_at');
            }
        });
    }
};
