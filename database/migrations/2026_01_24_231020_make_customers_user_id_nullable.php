<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (!Schema::hasColumn('customers', 'user_id')) {
            return;
        }

        DB::statement('ALTER TABLE customers DROP FOREIGN KEY customers_user_id_foreign');
        DB::statement('ALTER TABLE customers MODIFY user_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE customers ADD CONSTRAINT customers_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (!Schema::hasColumn('customers', 'user_id')) {
            return;
        }

        DB::statement('ALTER TABLE customers DROP FOREIGN KEY customers_user_id_foreign');
        DB::statement('ALTER TABLE customers MODIFY user_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE customers ADD CONSTRAINT customers_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }
};
