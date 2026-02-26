<?php

namespace Tests\Feature\ControlTower;

use App\Jobs\BuildControlTowerDailySnapshotJob;
use App\Models\Marketplace;
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

class ControlTowerModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_disabled_returns_not_found(): void
    {
        $user = $this->bootstrapClientWithControlTower(activeModule: false);

        $this->actingAs($user)
            ->get(route('portal.control-tower.index'))
            ->assertNotFound();
    }

    public function test_snapshot_job_writes_payload_and_signals(): void
    {
        $user = $this->bootstrapClientWithControlTower(activeModule: true);
        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $order = $this->createOrder($tenantId, $user->id, 'trendyol', 'CT-001');

        OrderProfitSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'trendyol',
            'order_id' => $order->id,
            'gross_revenue' => 200,
            'product_cost' => 150,
            'commission_amount' => 15,
            'shipping_amount' => 5,
            'service_amount' => 3,
            'campaign_amount' => 8,
            'ad_amount' => 2,
            'packaging_amount' => 1,
            'operational_amount' => 2,
            'return_risk_amount' => 4,
            'other_cost_amount' => 1,
            'net_profit' => 9,
            'net_margin' => 4.5,
            'calculation_version' => 'v1',
            'calculated_at' => now(),
            'meta' => [],
        ]);

        BuildControlTowerDailySnapshotJob::dispatchSync($user->id, now()->toDateString());

        $this->assertTrue(
            DB::table('control_tower_daily_snapshots')
                ->where('tenant_id', $tenantId)
                ->whereDate('date', now()->toDateString())
                ->exists()
        );
        $this->assertGreaterThan(
            0,
            DB::table('control_tower_signals')->where('tenant_id', $tenantId)->count()
        );
    }

    public function test_critical_signal_creates_control_tower_notification(): void
    {
        $user = $this->bootstrapClientWithControlTower(activeModule: true);
        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $order = $this->createOrder($tenantId, $user->id, 'amazon', 'CT-002');

        OrderProfitSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'amazon',
            'order_id' => $order->id,
            'gross_revenue' => 100,
            'product_cost' => 160,
            'commission_amount' => 20,
            'shipping_amount' => 10,
            'service_amount' => 0,
            'campaign_amount' => 15,
            'ad_amount' => 5,
            'packaging_amount' => 0,
            'operational_amount' => 0,
            'return_risk_amount' => 8,
            'other_cost_amount' => 0,
            'net_profit' => -118,
            'net_margin' => -118,
            'calculation_version' => 'v1',
            'calculated_at' => now(),
            'meta' => [],
        ]);

        BuildControlTowerDailySnapshotJob::dispatchSync($user->id, now()->toDateString());

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $tenantId,
            'source' => 'control_tower',
            'type' => 'critical',
        ]);
    }

    private function bootstrapClientWithControlTower(bool $activeModule): User
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
            'tenant_id' => null,
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
            'code' => 'feature.control_tower',
            'name' => 'Control Tower',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => $activeModule,
            'sort_order' => 1,
        ]);

        $plan = Plan::query()->create([
            'name' => 'Control Tower Plan',
            'slug' => 'control-tower-plan-'.$user->id.'-'.($activeModule ? 'on' : 'off'),
            'price' => 10,
            'billing_period' => 'monthly',
            'features' => ['modules' => ['feature.control_tower']],
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 10,
            'billing_period' => 'monthly',
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

    private function createOrder(int $tenantId, int $userId, string $marketplaceCode, string $orderNo): Order
    {
        $marketplace = Marketplace::query()->firstOrCreate(
            ['code' => $marketplaceCode],
            ['name' => strtoupper($marketplaceCode), 'is_active' => true]
        );

        return Order::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'marketplace_id' => $marketplace->id,
            'marketplace_order_id' => $orderNo,
            'order_number' => $orderNo,
            'status' => 'delivered',
            'total_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Control Tower Test',
            'order_date' => now()->toDateString(),
        ]);
    }
}
