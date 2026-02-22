<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'barcode')) {
                $table->string('barcode')->nullable()->after('sku');
            }
            if (!Schema::hasColumn('order_items', 'shipment_package_id')) {
                $table->string('shipment_package_id')->nullable()->after('barcode');
            }
        });

        if (!$this->hasIndex('order_items', 'order_items_tenant_order_barcode_package_unique')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->unique(
                    ['tenant_id', 'order_id', 'barcode', 'shipment_package_id'],
                    'order_items_tenant_order_barcode_package_unique'
                );
            });
        }

        if ($this->hasIndex('orders', 'orders_marketplace_order_id_unique')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_marketplace_order_id_unique');
            });
        }

        if (!$this->hasIndex('orders', 'orders_tenant_marketplace_order_unique')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique(
                    ['tenant_id', 'marketplace_integration_id', 'marketplace_account_id', 'marketplace_order_id'],
                    'orders_tenant_marketplace_order_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('orders', 'orders_tenant_marketplace_order_unique')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_tenant_marketplace_order_unique');
            });
        }

        if (!$this->hasIndex('orders', 'orders_marketplace_order_id_unique')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique('marketplace_order_id');
            });
        }

        if ($this->hasIndex('order_items', 'order_items_tenant_order_barcode_package_unique')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropUnique('order_items_tenant_order_barcode_package_unique');
            });
        }

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'shipment_package_id')) {
                $table->dropColumn('shipment_package_id');
            }
            if (Schema::hasColumn('order_items', 'barcode')) {
                $table->dropColumn('barcode');
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('{$table}')");
            foreach ($rows as $row) {
                if (($row->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $rows = DB::select('SHOW INDEX FROM ' . $table . ' WHERE Key_name = ?', [$indexName]);
            return count($rows) > 0;
        }

        if ($driver === 'pgsql') {
            $rows = DB::select(
                'SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $indexName]
            );
            return count($rows) > 0;
        }

        return false;
    }
};
