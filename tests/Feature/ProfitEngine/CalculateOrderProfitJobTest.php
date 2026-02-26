<?php

namespace Tests\Feature\ProfitEngine;

use App\Domains\Settlements\Models\OrderItem;
use App\Jobs\CalculateOrderProfitJob;
use App\Models\Marketplace;
use App\Models\MarketplaceFeeRule;
use App\Models\Module;
use App\Models\Order;
use App\Models\OrderProfitSnapshot;
use App\Models\ProfitCostProfile;
use App\Models\User;
use App\Models\UserModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalculateOrderProfitJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_profit_snapshot_when_module_is_enabled(): void
    {
        $user = $this->bootstrapUserWithProfitModule();
        $marketplace = Marketplace::query()->create([
            'name' => 'Trendyol',
            'code' => 'trendyol',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'marketplace_order_id' => 'PE-1001',
            'order_number' => 'PE-1001',
            'status' => 'delivered',
            'total_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Test User',
            'order_date' => now(),
        ]);

        OrderItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'order_id' => $order->id,
            'sku' => 'SKU-PE-1',
            'qty' => 1,
            'sale_price' => 100,
            'cost_price' => 50,
            'commission_amount' => 0,
            'shipping_amount' => 0,
            'service_fee_amount' => 0,
        ]);

        ProfitCostProfile::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Default',
            'packaging_cost' => 2,
            'operational_cost' => 3,
            'return_rate_default' => 5,
            'ad_cost_default' => 1,
            'is_default' => true,
        ]);

        MarketplaceFeeRule::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'marketplace' => 'trendyol',
            'commission_rate' => 10,
            'fixed_fee' => 0,
            'shipping_fee' => 4,
            'service_fee' => 1,
            'campaign_contribution_rate' => 2,
            'vat_rate' => 20,
            'priority' => 10,
            'active' => true,
        ]);

        CalculateOrderProfitJob::dispatchSync($order->id);

        $snapshot = OrderProfitSnapshot::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($snapshot);
        $this->assertFalse((bool) data_get($snapshot->meta, 'rule_missing', true));
    }

    public function test_it_marks_rule_and_cost_missing_flags_when_data_is_missing(): void
    {
        $user = $this->bootstrapUserWithProfitModule();
        $marketplace = Marketplace::query()->create([
            'name' => 'Trendyol',
            'code' => 'trendyol',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'marketplace_order_id' => 'PE-1002',
            'order_number' => 'PE-1002',
            'status' => 'delivered',
            'total_amount' => 120,
            'currency' => 'TRY',
            'customer_name' => 'Test User',
            'order_date' => now(),
        ]);

        OrderItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'order_id' => $order->id,
            'sku' => 'SKU-MISSING-COST',
            'qty' => 1,
            'sale_price' => 120,
            'cost_price' => 0,
            'commission_amount' => 0,
            'shipping_amount' => 0,
            'service_fee_amount' => 0,
        ]);

        ProfitCostProfile::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'name' => 'Default',
            'packaging_cost' => 0,
            'operational_cost' => 0,
            'return_rate_default' => 0,
            'ad_cost_default' => 0,
            'is_default' => true,
        ]);

        CalculateOrderProfitJob::dispatchSync($order->id);

        $snapshot = OrderProfitSnapshot::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($snapshot);
        $this->assertTrue((bool) data_get($snapshot->meta, 'rule_missing', false));
        $this->assertContains('SKU-MISSING-COST', (array) data_get($snapshot->meta, 'cost_missing_skus', []));
    }

    private function bootstrapUserWithProfitModule(): User
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        DB::table('tenants')->insert([
            'id' => $user->id,
            'name' => 'Tenant '.$user->id,
            'status' => 'active',
            'plan' => 'pro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->forceFill(['tenant_id' => $user->id])->save();

        $module = Module::query()->create([
            'code' => 'profit_engine',
            'name' => 'Profit Engine',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        UserModule::query()->create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        return $user;
    }
}

