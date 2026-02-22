<?php

namespace Database\Seeders;

use App\Domains\Settlements\Models\FeatureFlag;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\OrderItem;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\SettlementRule;
use App\Models\MarketplaceAccount;
use App\Models\Module;
use App\Models\Order;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class HakedisKontrolMerkeziSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::query()->firstOrCreate(
            ['email' => 'superadmin@local.test'],
            [
                'tenant_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles([Role::findOrCreate('SuperAdmin', 'sanctum')]);

        $tenantOwner = User::query()->firstOrCreate(
            ['email' => 'tenant-owner@local.test'],
            [
                'name' => 'Tenant Owner',
                'password' => Hash::make('password'),
                'role' => 'client',
                'is_active' => true,
            ]
        );

        if (!DB::table('tenants')->where('id', $tenantOwner->id)->exists()) {
            DB::table('tenants')->insert([
                'id' => $tenantOwner->id,
                'name' => 'Demo Tenant A.Ş.',
                'status' => 'active',
                'plan' => 'enterprise',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tenantAdmin = User::query()->firstOrCreate(
            ['email' => 'tenantadmin@local.test'],
            [
                'tenant_id' => $tenantOwner->id,
                'name' => 'Tenant Admin',
                'password' => Hash::make('password'),
                'role' => 'tenant_admin',
                'is_active' => true,
            ]
        );
        $tenantAdmin->syncRoles([Role::findOrCreate('TenantAdmin', 'sanctum')]);

        $integration = MarketplaceIntegration::query()->firstOrCreate(
            ['code' => 'trendyol'],
            ['name' => 'Trendyol', 'is_enabled' => true]
        );

        $account = MarketplaceAccount::query()->firstOrCreate(
            ['tenant_id' => $tenantOwner->id, 'marketplace' => 'trendyol', 'store_name' => 'Demo Store'],
            [
                'marketplace_integration_id' => $integration->id,
                'connector_key' => 'trendyol',
                'credentials' => ['api_key' => 'demo', 'api_secret' => 'demo'],
                'status' => 'active',
                'is_active' => true,
            ]
        );

        Module::query()->updateOrCreate(
            ['code' => 'feature.hakedis'],
            [
                'name' => 'Hakediş Kontrol Merkezi',
                'description' => 'Payout, mutabakat ve sapma merkezi.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
        app(EntitlementService::class)->grantModule($tenantOwner, 'feature.hakedis');

        FeatureFlag::query()->withoutGlobalScope('tenant_scope')->updateOrCreate(
            ['tenant_id' => $tenantOwner->id, 'key' => 'hakedis_module'],
            ['enabled' => true]
        );

        SettlementRule::query()->withoutGlobalScope('tenant_scope')->updateOrCreate(
            ['tenant_id' => $tenantOwner->id, 'marketplace_integration_id' => $integration->id],
            [
                'ruleset' => [
                    'cycle_type' => 'DELIVERY_PLUS_DAYS',
                    'cycle_days' => 7,
                    'vat_mode' => 'INCLUSIVE',
                    'default_service_fee' => ['amount' => 10.00, 'vat_rate' => 20],
                    'shipping_calc' => 'API_IF_INVOICED_ELSE_DESI',
                    'tolerances' => ['amount' => 1.00, 'percent' => 0.5],
                ],
            ]
        );

        $order = Order::query()->firstOrCreate(
            ['marketplace_order_id' => 'TY-DEMO-ORDER-001'],
            [
                'tenant_id' => $tenantOwner->id,
                'user_id' => $tenantOwner->id,
                'marketplace_integration_id' => $integration->id,
                'marketplace_account_id' => $account->id,
                'status' => 'DELIVERED',
                'total_amount' => 600,
                'commission_amount' => 60,
                'net_amount' => 480,
                'currency' => 'TRY',
                'customer_name' => 'Demo Customer',
                'order_date' => now()->subDays(5),
            ]
        );

        OrderItem::query()->withoutGlobalScope('tenant_scope')->firstOrCreate(
            ['tenant_id' => $tenantOwner->id, 'order_id' => $order->id, 'sku' => 'SKU-001'],
            [
                'variant_id' => 'VAR-001',
                'qty' => 1,
                'sale_price' => 600,
                'sale_vat' => 100,
                'cost_price' => 350,
                'cost_vat' => 58.33,
                'commission_amount' => 60,
                'commission_vat' => 10,
                'shipping_amount' => 25,
                'shipping_vat' => 4.17,
                'service_fee_amount' => 10,
                'service_fee_vat' => 1.67,
                'calculated' => ['net_vat' => 25.83, 'profit' => 154.17],
            ]
        );

        Payout::query()->withoutGlobalScope('tenant_scope')->firstOrCreate(
            [
                'tenant_id' => $tenantOwner->id,
                'marketplace_account_id' => $account->id,
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->toDateString(),
            ],
            [
                'marketplace_integration_id' => $integration->id,
                'payout_reference' => 'TY-DEMO-PO-001',
                'expected_date' => now()->addDays(2)->toDateString(),
                'expected_amount' => 154.17,
                'paid_amount' => 150.00,
                'paid_date' => now()->toDateString(),
                'currency' => 'TRY',
                'status' => 'DISCREPANCY',
            ]
        );
    }
}
