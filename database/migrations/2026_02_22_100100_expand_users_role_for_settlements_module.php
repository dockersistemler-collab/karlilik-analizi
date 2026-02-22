<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','client','support_agent','tenant_admin','finance','viewer') NOT NULL DEFAULT 'client'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','client','support_agent') NOT NULL DEFAULT 'client'");
        }
    }
};

