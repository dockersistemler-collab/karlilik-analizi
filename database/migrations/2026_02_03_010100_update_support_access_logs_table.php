<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('support_access_logs')) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE support_access_logs MODIFY super_admin_id BIGINT UNSIGNED NULL');
        }

        Schema::table('support_access_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('support_access_logs', 'actor_user_id')) {
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('support_access_logs', 'actor_role')) {
                $table->string('actor_role')->nullable();
            }
            if (!Schema::hasColumn('support_access_logs', 'source_type')) {
                $table->string('source_type')->nullable();
            }
            if (!Schema::hasColumn('support_access_logs', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable();
            }
            if (!Schema::hasColumn('support_access_logs', 'expires_at')) {
                $table->dateTime('expires_at')->nullable();
            }
        });

        Schema::table('support_access_logs', function (Blueprint $table) {
            $table->index(['actor_user_id', 'target_user_id', 'started_at'], 'support_access_logs_actor_target_started_idx');
            $table->index(['source_type', 'source_id'], 'support_access_logs_source_idx');
            $table->index('expires_at', 'support_access_logs_expires_at_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('support_access_logs')) {
            return;
        }

        Schema::table('support_access_logs', function (Blueprint $table) {
            $table->dropIndex('support_access_logs_actor_target_started_idx');
            $table->dropIndex('support_access_logs_source_idx');
            $table->dropIndex('support_access_logs_expires_at_idx');

            $table->dropConstrainedForeignId('actor_user_id');
            $table->dropColumn(['actor_role', 'source_type', 'source_id', 'expires_at']);
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE support_access_logs MODIFY super_admin_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
