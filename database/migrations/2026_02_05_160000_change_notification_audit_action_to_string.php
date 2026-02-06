<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_audit_logs', function (Blueprint $table) {
            $table->string('action', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('notification_audit_logs', function (Blueprint $table) {
            $table->enum('action', [
                'view',
                'mark_read',
                'settings_change',
                'email_dispatched',
                'email_deferred',
                'email_suppressed',
                'email_failed',
                'incident_ack',
                'incident_resolve',
            ])->change();
        });
    }
};
