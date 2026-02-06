<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE orders DROP FOREIGN KEY orders_marketplace_id_foreign');
        DB::statement('ALTER TABLE orders MODIFY marketplace_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_marketplace_id_foreign FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE orders DROP FOREIGN KEY orders_marketplace_id_foreign');
        DB::statement('ALTER TABLE orders MODIFY marketplace_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_marketplace_id_foreign FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id) ON DELETE CASCADE');
    }
};
