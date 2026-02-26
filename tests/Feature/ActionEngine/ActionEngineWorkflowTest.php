<?php

namespace Tests\Feature\ActionEngine;

use App\Domains\Settlements\Models\OrderItem;
use App\Jobs\RunActionEngineDailyJob;
use App\Models\ActionRecommendation;
use App\Models\Marketplace;
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

class ActionEngineWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_critical_risk_and_negative_profit_creates_recommendation(): void
    {
        $user = $this->bootstrapUserWithActionEngine();
        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $date = '2026-02-24';

        $marketplace = Marketplace::query()->create([
            'name' => 'Amazon',
            'code' => 'amazon',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'marketplace_order_id' => 'ACT-001',
            'order_number' => 'ACT-001',
            'status' => 'delivered',
            'total_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Test',
            'order_date' => $date,
        ]);

        OrderItem::query()->create([
            'tenant_id' => $tenantId,
            'order_id' => $order->id,
            'sku' => 'SKU-ACT-1',
            'qty' => 1,
            'sale_price' => 100,
            'cost_price' => 130,
        ]);

        OrderProfitSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'amazon',
            'order_id' => $order->id,
            'gross_revenue' => 100,
            'product_cost' => 130,
            'commission_amount' => 10,
            'shipping_amount' => 5,
            'service_amount' => 0,
            'campaign_amount' => 0,
            'ad_amount' => 0,
            'packaging_amount' => 0,
            'operational_amount' => 0,
            'return_risk_amount' => 0,
            'other_cost_amount' => 0,
            'net_profit' => -45,
            'net_margin' => -45,
            'calculation_version' => 'v1',
            'calculated_at' => now(),
        ]);

        MarketplaceRiskScore::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'amazon',
            'date' => $date,
            'risk_score' => 92,
            'status' => 'critical',
            'reasons' => [
                'drivers' => [
                    ['metric' => 'odr', 'severity' => 90],
                    ['metric' => 'late_shipment_rate', 'severity' => 75],
                ],
            ],
        ]);

        RunActionEngineDailyJob::dispatchSync($user->id, $date);

        $this->assertDatabaseHas('action_recommendations', [
            'tenant_id' => $tenantId,
            'marketplace' => 'amazon',
            'status' => 'open',
        ]);
    }

    public function test_dedupe_works_for_same_tuple(): void
    {
        $user = $this->bootstrapUserWithActionEngine();
        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $date = '2026-02-24';

        MarketplaceRiskScore::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'trendyol',
            'date' => $date,
            'risk_score' => 80,
            'status' => 'critical',
            'reasons' => ['drivers' => [['metric' => 'late_shipment_rate']]],
        ]);

        RunActionEngineDailyJob::dispatchSync($user->id, $date);
        RunActionEngineDailyJob::dispatchSync($user->id, $date);

        $count = ActionRecommendation::query()
            ->where('tenant_id', $tenantId)
            ->where('marketplace', 'trendyol')
            ->whereDate('date', $date)
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_apply_and_dismiss_updates_status(): void
    {
        $user = $this->bootstrapUserWithActionEngine();
        $tenantId = (int) ($user->tenant_id ?: $user->id);

        $apply = ActionRecommendation::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'date' => '2026-02-24',
            'marketplace' => 'n11',
            'sku' => '',
            'severity' => 'high',
            'title' => 'apply me',
            'description' => 'x',
            'action_type' => 'SHIPPING_SLA_FIX',
            'status' => 'open',
        ]);
        $dismiss = ActionRecommendation::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'date' => '2026-02-24',
            'marketplace' => 'n11',
            'sku' => 'SKU-X',
            'severity' => 'medium',
            'title' => 'dismiss me',
            'description' => 'x',
            'action_type' => 'RULE_REVIEW',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->post(route('portal.action-engine.apply', $apply))
            ->assertRedirect();
        $this->actingAs($user)
            ->post(route('portal.action-engine.dismiss', $dismiss))
            ->assertRedirect();

        $this->assertDatabaseHas('action_recommendations', [
            'id' => $apply->id,
            'status' => 'applied',
        ]);
        $this->assertDatabaseHas('action_recommendations', [
            'id' => $dismiss->id,
            'status' => 'dismissed',
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
            'slug' => 'pro-action-'.$user->id,
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

