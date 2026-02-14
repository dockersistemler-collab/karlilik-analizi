<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'critical_stock_level')) {
                $table->integer('critical_stock_level')->default(5)->after('stock_quantity');
            }
        });

        Schema::table('marketplace_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_accounts', 'connector_key')) {
                $table->string('connector_key')->nullable()->after('marketplace');
                $table->index(['tenant_id', 'connector_key'], 'marketplace_accounts_tenant_connector_idx');
            }
            if (!Schema::hasColumn('marketplace_accounts', 'credentials_json')) {
                $table->json('credentials_json')->nullable()->after('credentials');
            }
            if (!Schema::hasColumn('marketplace_accounts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
            if (!Schema::hasColumn('marketplace_accounts', 'last_sync_at')) {
                $table->timestamp('last_sync_at')->nullable()->after('last_synced_at');
            }
        });

        DB::table('marketplace_accounts')
            ->whereNull('connector_key')
            ->update(['connector_key' => DB::raw('marketplace')]);
        DB::table('marketplace_accounts')
            ->whereNull('credentials_json')
            ->update(['credentials_json' => DB::raw('credentials')]);
        DB::table('marketplace_accounts')
            ->whereNull('last_sync_at')
            ->update(['last_sync_at' => DB::raw('last_synced_at')]);
        DB::table('marketplace_accounts')
            ->where('status', 'active')
            ->update(['is_active' => true]);
        DB::table('marketplace_accounts')
            ->where('status', '!=', 'active')
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'critical_stock_level')) {
                $table->dropColumn('critical_stock_level');
            }
        });

        Schema::table('marketplace_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_accounts', 'connector_key')) {
                $table->dropIndex('marketplace_accounts_tenant_connector_idx');
                $table->dropColumn('connector_key');
            }
            if (Schema::hasColumn('marketplace_accounts', 'credentials_json')) {
                $table->dropColumn('credentials_json');
            }
            if (Schema::hasColumn('marketplace_accounts', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('marketplace_accounts', 'last_sync_at')) {
                $table->dropColumn('last_sync_at');
            }
        });
    }
};
