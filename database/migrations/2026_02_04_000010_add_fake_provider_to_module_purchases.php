<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE module_purchases MODIFY provider ENUM('iyzico','manual','fake')");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("UPDATE module_purchases SET provider='manual' WHERE provider='fake'");
        DB::statement("ALTER TABLE module_purchases MODIFY provider ENUM('iyzico','manual')");
    }
};
