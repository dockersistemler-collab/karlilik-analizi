<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cargo_provider_installations')) {
            return;
        }

        DB::statement('ALTER TABLE `cargo_provider_installations` MODIFY `credentials_json` TEXT');
    }

    public function down(): void
    {
        if (!Schema::hasTable('cargo_provider_installations')) {
            return;
        }

        DB::statement('ALTER TABLE `cargo_provider_installations` MODIFY `credentials_json` JSON');
    }
};
