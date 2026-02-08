<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('support_access_logs')) {
            Schema::create('support_access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('super_admin_id')->constrained('users');
                $table->foreignId('target_user_id')->constrained('users');
                $table->dateTime('started_at');
                $table->dateTime('ended_at')->nullable();
                $table->string('ip')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('reason');
                $table->enum('scope', ['read_only'])->default('read_only');
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        $indexName = 'support_access_logs_sa_tu_started_idx';
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('support_access_logs', function (Blueprint $table) use ($indexName) {
                $table->index(['super_admin_id', 'target_user_id', 'started_at'], $indexName);
            });

            return;
        }

        $existing = DB::select('SHOW INDEX FROM support_access_logs WHERE Key_name = ?', [$indexName]);
        if (empty($existing)) {
            Schema::table('support_access_logs', function (Blueprint $table) use ($indexName) {
                $table->index(['super_admin_id', 'target_user_id', 'started_at'], $indexName);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_access_logs');
    }
};
