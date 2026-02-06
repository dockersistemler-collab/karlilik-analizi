<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE notification_audit_logs MODIFY action ENUM('view','mark_read','settings_change','email_dispatched','email_deferred','email_suppressed','email_failed')"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE notification_audit_logs MODIFY action ENUM('view','mark_read','settings_change')"
        );
    }
};
