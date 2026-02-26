<?php

namespace Tests\Feature\ActionEngine;

use App\Domains\Settlements\Models\OrderItem;
use App\Models\ActionEngineCalibration;
use App\Models\ActionRecommendation;
use App\Models\Marketplace;
use App\Models\MarketplaceExternalShock;
use App\Models\MarketplacePriceHistory;
use App\Models\Module;
use App\Models\Order;
use App\Models\OrderProfitSnapshot;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserModule;
use App\Services\ActionEngine\CalibrationEngine;
use App\Services\ActionEngine\CampaignCalendarApplier;
use App\Services\ActionEngine\CampaignCsvImporter;
use App\Services\ActionEngine\ImpactSimulator;
use App\Services\ActionEngine\PriceHistoryBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActionEnginePhase4Test extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_import_marks_promo_day_flags(): void
    {
        $user = $this->bootstrapUser();
        $tenantId = (int) ($user->tenant_id ?: $user->id);

        $marketplace = Marketplace::query()->create([
            'name' => 'Trendyol',
            'code' => 'trendyol',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'marketplace_order_id' => 'PH-1',
            'order_number' => 'PH-1',
            'status' => 'delivered',
            'total_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Test',
            'order_date' => '2026-02-24',
        ]);
        OrderItem::query()->create([
            'tenant_id' => $tenantId,
            'order_id' => $order->id,
            'sku' => 'SKU-C1',
            'qty' => 2,
            'sale_price' => 50,
            'cost_price' => 20,
        ]);

        app(PriceHistoryBuilder::class)->buildRange(
            $tenantId,
            (int) $user->id,
            CarbonImmutable::parse('2026-02-24'),
            CarbonImmutable::parse('2026-02-24')
        );

        $csvPath = tempnam(sys_get_temp_dir(), 'cmp');
        file_put_contents($csvPath, implode("\n", [
            'marketplace,campaign_id,campaign_name,start_date,end_date,sku,discount_rate',
            'trendyol,CMP-1,Bahar,2026-02-24,2026-02-24,SKU-C1,15',
        ]));

        app(CampaignCsvImporter::class)->import($tenantId, (int) $user->id, $csvPath);
        app(CampaignCalendarApplier::class)->applyForTenant($tenantId, (int) $user->id);

        $this->assertDatabaseHas('marketplace_price_history', [
            'tenant_id' => $tenantId,
            'marketplace' => 'trendyol',
            'sku' => 'SKU-C1',
            'is_promo_day' => 1,
            'promo_source' => 'import',
        ]);
        $this->assertDatabaseHas('marketplace_external_shocks', [
            'tenant_id' => $tenantId,
            'marketplace' => 'trendyol',
            'sku' => 'SKU-C1',
            'shock_type' => 'CAMPAIGN',
            'detected_by' => 'import',
        ]);
    }

    public function test_calibration_excludes_campaign_days_and_sets_diagnostics(): void
    {
        $user = $this->bootstrapUser();
        $tenantId = (int) ($user->tenant_id ?: $user->id);

        for ($i = 0; $i < 20; $i++) {
            MarketplacePriceHistory::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'marketplace' => 'amazon',
                'sku' => 'SKU-K1',
                'date' => CarbonImmutable::parse('2026-02-01')->addDays($i)->toDateString(),
                'unit_price' => 100 + $i,
                'units_sold' => 30 - (int) floor($i / 2),
                'revenue' => (100 + $i) * (30 - (int) floor($i / 2)),
                'promo_source' => $i < 3 ? 'import' : null,
                'shock_flags' => $i === 10 ? ['OUTLIER_DEMAND'] : [],
                'is_shipping_shock' => $i === 12,
                'is_fee_shock' => false,
            ]);
        }

        app(CalibrationEngine::class)->runForTenant(
            $tenantId,
            (int) $user->id,
            CarbonImmutable::parse('2026-02-20'),
            45
        );

        $calibration = ActionEngineCalibration::query()
            ->where('tenant_id', $tenantId)
            ->where('marketplace', 'amazon')
            ->whereNull('sku')
            ->first();

        $this->assertNotNull($calibration);
        $this->assertGreaterThan(0, (int) data_get($calibration->diagnostics, 'excluded.campaign_import', 0));
        $this->assertGreaterThan(0, (int) data_get($calibration->diagnostics, 'excluded.outlier', 0));
    }

    public function test_impact_simulator_persists_delta(): void
    {
        $user = $this->bootstrapUser();
        $tenantId = (int) ($user->tenant_id ?: $user->id);

        MarketplacePriceHistory::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'amazon',
            'sku' => 'SKU-I1',
            'date' => '2026-02-24',
            'unit_price' => 100,
            'units_sold' => 20,
            'revenue' => 2000,
        ]);

        $marketplace = Marketplace::query()->create([
            'name' => 'Amazon',
            'code' => 'amazon',
            'is_active' => true,
        ]);
        $order = Order::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'marketplace_order_id' => 'IM-1',
            'order_number' => 'IM-1',
            'status' => 'delivered',
            'total_amount' => 120,
            'currency' => 'TRY',
            'customer_name' => 'Test',
            'order_date' => '2026-02-24',
        ]);
        OrderItem::query()->create([
            'tenant_id' => $tenantId,
            'order_id' => $order->id,
            'sku' => 'SKU-I1',
            'qty' => 1,
            'sale_price' => 120,
            'cost_price' => 80,
        ]);
        OrderProfitSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'amazon',
            'order_id' => $order->id,
            'gross_revenue' => 120,
            'product_cost' => 80,
            'commission_amount' => 10,
            'shipping_amount' => 5,
            'service_amount' => 0,
            'campaign_amount' => 0,
            'ad_amount' => 0,
            'packaging_amount' => 0,
            'operational_amount' => 0,
            'return_risk_amount' => 0,
            'other_cost_amount' => 0,
            'net_profit' => 25,
            'net_margin' => 20.83,
            'calculation_version' => 'v1',
            'calculated_at' => now(),
        ]);
        ActionEngineCalibration::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'amazon',
            'sku' => 'SKU-I1',
            'window_days' => 45,
            'elasticity' => -1.1,
            'margin_uplift_factor' => 1.08,
            'ad_pause_revenue_drop_pct' => 10,
            'confidence' => 78,
            'diagnostics' => ['used_rows' => 30],
            'calculated_at' => now(),
        ]);
        $recommendation = ActionRecommendation::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'date' => '2026-02-24',
            'marketplace' => 'amazon',
            'sku' => 'SKU-I1',
            'severity' => 'high',
            'title' => 'fiyat artir',
            'description' => 'test',
            'action_type' => 'PRICE_INCREASE',
            'suggested_payload' => ['target_price_increase_pct' => 5],
            'status' => 'open',
        ]);

        $impact = app(ImpactSimulator::class)->simulateAndStore($recommendation);

        $this->assertNotNull($impact->calculated_at);
        $this->assertNotEmpty((array) $impact->delta);
        $this->assertArrayHasKey('net_profit', (array) $impact->delta);
    }

    private function bootstrapUser(): User
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
            'slug' => 'pro-phase4-'.$user->id,
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

