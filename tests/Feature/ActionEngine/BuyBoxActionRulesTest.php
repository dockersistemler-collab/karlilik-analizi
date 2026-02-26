<?php

namespace Tests\Feature\ActionEngine;

use App\Domains\Settlements\Models\OrderItem;
use App\Jobs\RunActionEngineDailyJob;
use App\Models\BuyBoxScore;
use App\Models\Marketplace;
use App\Models\MarketplaceOfferSnapshot;
use App\Models\MarketplaceRiskScore;
use App\Models\Module;
use App\Models\Order;
use App\Models\OrderProfitSnapshot;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuyBoxActionRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_losing_price_gap_and_margin_ok_creates_price_adjust_recommendation(): void
    {
        $user = $this->bootstrapUserWithActionEngine();
        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $date = '2026-02-24';

        $this->seedProfitForSku($user, 'amazon', 'SKU-BB-1', $date, 20);
        $this->seedBuyBoxScore($tenantId, 'amazon', 'SKU-BB-1', $date, [
            'is_winning' => false,
            'our_price' => 110,
            'competitor_best_price' => 100,
            'stock_available' => 20,
            'drivers' => [
                ['metric' => 'price_competitiveness', 'penalty' => 18],
                ['metric' => 'store_score', 'penalty' => 7],
            ],
        ]);

        RunActionEngineDailyJob::dispatchSync($user->id, $date);

        $this->assertDatabaseHas('action_recommendations', [
            'tenant_id' => $tenantId,
            'marketplace' => 'amazon',
            'sku' => 'SKU-BB-1',
            'action_type' => 'PRICE_ADJUST',
            'status' => 'open',
        ]);
    }

    public function test_store_score_driver_creates_shipping_sla_fix_recommendation(): void
    {
        $user = $this->bootstrapUserWithActionEngine();
        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $date = '2026-02-24';

        $this->seedProfitForSku($user, 'trendyol', 'SKU-BB-2', $date, 12);
        $this->seedBuyBoxScore($tenantId, 'trendyol', 'SKU-BB-2', $date, [
            'is_winning' => false,
            'our_price' => 100,
            'competitor_best_price' => 100,
            'stock_available' => 10,
            'drivers' => [
                ['metric' => 'store_score', 'penalty' => 21],
                ['metric' => 'shipping_speed', 'penalty' => 16],
            ],
        ]);

        RunActionEngineDailyJob::dispatchSync($user->id, $date);

        $this->assertDatabaseHas('action_recommendations', [
            'tenant_id' => $tenantId,
            'marketplace' => 'trendyol',
            'sku' => 'SKU-BB-2',
            'action_type' => 'SHIPPING_SLA_FIX',
            'status' => 'open',
        ]);
    }

    public function test_risk_critical_blocks_price_adjust_recommendation(): void
    {
        $user = $this->bootstrapUserWithActionEngine();
        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $date = '2026-02-24';

        $this->seedProfitForSku($user, 'n11', 'SKU-BB-3', $date, 30);
        $this->seedBuyBoxScore($tenantId, 'n11', 'SKU-BB-3', $date, [
            'is_winning' => false,
            'our_price' => 108,
            'competitor_best_price' => 100,
            'stock_available' => 8,
            'drivers' => [
                ['metric' => 'price_competitiveness', 'penalty' => 20],
                ['metric' => 'store_score', 'penalty' => 12],
            ],
        ]);

        MarketplaceRiskScore::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'n11',
            'date' => $date,
            'risk_score' => 90,
            'status' => 'critical',
            'reasons' => ['drivers' => [['metric' => 'late_shipment_rate']]],
        ]);

        RunActionEngineDailyJob::dispatchSync($user->id, $date);

        $this->assertDatabaseMissing('action_recommendations', [
            'tenant_id' => $tenantId,
            'marketplace' => 'n11',
            'sku' => 'SKU-BB-3',
            'action_type' => 'PRICE_ADJUST',
        ]);
    }

    private function seedBuyBoxScore(int $tenantId, string $marketplace, string $sku, string $date, array $attrs): void
    {
        $snapshot = MarketplaceOfferSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'marketplace' => $marketplace,
            'date' => $date,
            'sku' => $sku,
            'is_winning' => (bool) ($attrs['is_winning'] ?? false),
            'our_price' => $attrs['our_price'] ?? null,
            'competitor_best_price' => $attrs['competitor_best_price'] ?? null,
            'stock_available' => $attrs['stock_available'] ?? null,
            'store_score' => $attrs['store_score'] ?? 70,
            'shipping_speed_score' => $attrs['shipping_speed_score'] ?? 80,
            'promo_flag' => (bool) ($attrs['promo_flag'] ?? false),
            'source' => 'manual',
            'meta' => ['seed' => true],
        ]);

        BuyBoxScore::query()->create([
            'tenant_id' => $tenantId,
            'marketplace' => $marketplace,
            'date' => $date,
            'sku' => $sku,
            'buybox_score' => 45,
            'status' => 'losing',
            'win_probability' => 0.35,
            'drivers' => $attrs['drivers'] ?? [],
            'snapshot_id' => $snapshot->id,
        ]);
    }

    private function seedProfitForSku(User $user, string $marketplaceCode, string $sku, string $date, float $netMargin): void
    {
        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $marketplace = Marketplace::query()->firstOrCreate(
            ['code' => $marketplaceCode],
            ['name' => strtoupper($marketplaceCode), 'is_active' => true]
        );

        $order = Order::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'marketplace_order_id' => 'BB-'.$marketplaceCode.'-'.$sku,
            'order_number' => 'BB-'.$marketplaceCode.'-'.$sku,
            'status' => 'delivered',
            'total_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Test',
            'order_date' => $date,
        ]);

        OrderItem::query()->create([
            'tenant_id' => $tenantId,
            'order_id' => $order->id,
            'sku' => $sku,
            'qty' => 1,
            'sale_price' => 100,
            'cost_price' => 70,
        ]);

        OrderProfitSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => $marketplaceCode,
            'order_id' => $order->id,
            'gross_revenue' => 100,
            'product_cost' => 70,
            'commission_amount' => 5,
            'shipping_amount' => 3,
            'service_amount' => 0,
            'campaign_amount' => 0,
            'ad_amount' => 0,
            'packaging_amount' => 0,
            'operational_amount' => 0,
            'return_risk_amount' => 0,
            'other_cost_amount' => 0,
            'net_profit' => 100 * ($netMargin / 100),
            'net_margin' => $netMargin,
            'calculation_version' => 'v1',
            'calculated_at' => now(),
        ]);
    }

    private function bootstrapUserWithActionEngine(): User
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

        $plan = Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro-buybox-action-'.$user->id,
            'price' => 1,
            'billing_period' => 'monthly',
            'features' => ['modules' => ['action_engine']],
            'is_active' => true,
        ]);
        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 1,
            'billing_period' => 'monthly',
        ]);

        $module = Module::query()->create([
            'code' => 'action_engine',
            'name' => 'Action Engine',
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
