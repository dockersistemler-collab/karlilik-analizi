<?php

namespace Tests\Feature\Api;

use App\Domains\Settlements\Actions\BuildExpectedPayoutsAction;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\OrderItem;
use App\Domains\Settlements\Models\SettlementRule;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuildExpectedPayoutsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_expected_payout_from_order_items(): void
    {
        $tenantOwner = User::factory()->create(['role' => 'client', 'email' => 'owner-expected@test.local']);
        DB::table('tenants')->insert([
            'id' => $tenantOwner->id,
            'name' => 'Tenant Expected',
            'status' => 'active',
            'plan' => 'pro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $integration = MarketplaceIntegration::query()->create([
            'code' => 'trendyol',
            'name' => 'Trendyol',
            'is_enabled' => true,
        ]);
        $account = MarketplaceAccount::query()->create([
            'tenant_id' => $tenantOwner->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace' => 'trendyol',
            'connector_key' => 'trendyol',
            'store_name' => 'Store',
            'credentials' => ['api_key' => 'x'],
            'status' => 'active',
        ]);

        SettlementRule::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantOwner->id,
            'marketplace_integration_id' => $integration->id,
            'ruleset' => ['cycle_type' => 'DELIVERY_PLUS_DAYS', 'cycle_days' => 7],
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenantOwner->id,
            'user_id' => $tenantOwner->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $account->id,
            'marketplace_order_id' => 'ORD-1',
            'status' => 'DELIVERED',
            'total_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'A',
            'order_date' => now(),
        ]);

        OrderItem::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantOwner->id,
            'order_id' => $order->id,
            'sku' => 'SKU-1',
            'qty' => 1,
            'sale_price' => 100,
            'calculated' => ['profit' => 42.25, 'net_vat' => 5.10],
        ]);

        $payout = app(BuildExpectedPayoutsAction::class)->execute(
            $account->id,
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );

        $this->assertEquals(42.25, (float) $payout->expected_amount);
        $this->assertEquals('EXPECTED', $payout->status);
        $this->assertCount(1, $payout->transactions);
    }
}
