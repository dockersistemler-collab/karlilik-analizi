<?php

namespace Tests\Feature\MarketplaceRisk;

use App\Jobs\CalculateMarketplaceRiskJob;
use App\Models\MarketplaceKpiSnapshot;
use App\Models\MarketplaceRiskProfile;
use App\Models\MarketplaceRiskScore;
use App\Models\Module;
use App\Models\User;
use App\Models\UserModule;
use App\Services\MarketplaceRisk\RiskCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceRiskEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_performance_score_drop_increases_risk_score(): void
    {
        $user = $this->bootstrapUserWithModule();
        $tenantId = (int) ($user->tenant_id ?: $user->id);

        MarketplaceKpiSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'trendyol',
            'date' => '2026-02-20',
            'late_shipment_rate' => 1,
            'cancellation_rate' => 0.5,
            'return_rate' => 3,
            'performance_score' => 92,
            'rating_score' => 4.7,
            'source' => 'manual',
        ]);

        MarketplaceKpiSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'trendyol',
            'date' => '2026-02-21',
            'late_shipment_rate' => 1,
            'cancellation_rate' => 0.5,
            'return_rate' => 3,
            'performance_score' => 65,
            'rating_score' => 4.7,
            'source' => 'manual',
        ]);

        CalculateMarketplaceRiskJob::dispatchSync($user->id, '2026-02-20');
        CalculateMarketplaceRiskJob::dispatchSync($user->id, '2026-02-21');

        $scoreBefore = (float) MarketplaceRiskScore::query()
            ->where('tenant_id', $tenantId)
            ->where('marketplace', 'trendyol')
            ->whereDate('date', '2026-02-20')
            ->value('risk_score');
        $scoreAfter = (float) MarketplaceRiskScore::query()
            ->where('tenant_id', $tenantId)
            ->where('marketplace', 'trendyol')
            ->whereDate('date', '2026-02-21')
            ->value('risk_score');

        $this->assertGreaterThan($scoreBefore, $scoreAfter);
    }

    public function test_missing_metrics_are_renormalized(): void
    {
        $user = $this->bootstrapUserWithModule();
        $tenantId = (int) ($user->tenant_id ?: $user->id);

        $profile = MarketplaceRiskProfile::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'amazon',
            'name' => 'Default',
            'weights' => [
                'late_shipment_rate' => 0.5,
                'cancellation_rate' => 0.5,
            ],
            'thresholds' => ['warning' => 45, 'critical' => 70],
            'metric_thresholds' => [
                'late_shipment_rate' => ['warning' => 2, 'critical' => 6, 'direction' => 'higher_worse'],
                'cancellation_rate' => ['warning' => 2, 'critical' => 6, 'direction' => 'higher_worse'],
            ],
            'is_default' => true,
        ]);

        $snapshot = MarketplaceKpiSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'amazon',
            'date' => '2026-02-21',
            'late_shipment_rate' => 6,
            'cancellation_rate' => null,
            'source' => 'manual',
        ]);

        $result = app(RiskCalculator::class)->calculate(
            $tenantId,
            'amazon',
            CarbonImmutable::parse('2026-02-21'),
            $snapshot,
            $profile
        );

        $this->assertEqualsWithDelta(100.0, (float) $result['risk_score'], 0.0001);
        $this->assertContains('cancellation_rate', (array) data_get($result, 'reasons.missing_metrics', []));
    }

    public function test_warning_or_critical_creates_in_app_notification(): void
    {
        $user = $this->bootstrapUserWithModule();
        $tenantId = (int) ($user->tenant_id ?: $user->id);

        MarketplaceKpiSnapshot::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'n11',
            'date' => '2026-02-21',
            'late_shipment_rate' => 12,
            'cancellation_rate' => 7,
            'return_rate' => 18,
            'performance_score' => 60,
            'rating_score' => 3.9,
            'source' => 'manual',
        ]);

        CalculateMarketplaceRiskJob::dispatchSync($user->id, '2026-02-21');

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'marketplace' => 'n11',
            'source' => 'marketplace_risk',
            'channel' => 'in_app',
        ]);
    }

    private function bootstrapUserWithModule(): User
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
            'code' => 'marketplace_risk',
            'name' => 'Marketplace Risk',
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

