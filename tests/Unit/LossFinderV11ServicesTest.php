<?php

namespace Tests\Unit;

use App\Domains\Settlements\Models\LossFinding;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\Reconciliation;
use App\Domains\Settlements\Models\ReconciliationRule;
use App\Domains\Settlements\Services\ConfidenceScoringService;
use App\Domains\Settlements\Services\LossPatternAggregatorService;
use App\Domains\Settlements\Services\ReconcileRegressionGuardService;
use App\Domains\Settlements\Services\TenantRuleResolver;
use App\Models\MarketplaceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LossFinderV11ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_confidence_scoring_prioritizes_high_severity_and_amount(): void
    {
        $service = app(ConfidenceScoringService::class);

        $high = $service->score([
            'code' => 'LOSS_MISSING_IN_PAYOUT',
            'severity' => 'high',
            'amount' => 120,
        ]);
        $low = $service->score([
            'code' => 'MICRO_LOSS_AGGREGATOR',
            'severity' => 'low',
            'amount' => 1.5,
        ]);

        $this->assertGreaterThan($low, $high);
        $this->assertGreaterThanOrEqual(90, $high);
    }

    public function test_tenant_rule_resolver_prefers_tenant_override(): void
    {
        [$tenantId] = $this->bootstrapTenant();

        ReconciliationRule::query()->withoutGlobalScope('tenant_scope')->create([
            'marketplace' => 'trendyol',
            'rule_type' => 'tolerance',
            'key' => 'default',
            'value' => ['amount' => 0.35],
            'priority' => 1,
            'is_active' => true,
            'scope' => 'global',
            'scope_type' => 'global',
        ]);

        ReconciliationRule::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantId,
            'scope' => 'tenant',
            'scope_type' => 'tenant',
            'scope_key' => "tenant:{$tenantId}",
            'marketplace' => 'trendyol',
            'rule_type' => 'tolerance',
            'key' => 'default',
            'value' => ['amount' => 0.08],
            'priority' => 10,
            'is_active' => true,
        ]);

        $resolver = app(TenantRuleResolver::class);
        $this->assertSame(0.08, $resolver->tolerance($tenantId, 'trendyol', 0.01));
    }

    public function test_loss_pattern_aggregator_groups_findings_by_pattern(): void
    {
        [$tenantId, $payout] = $this->bootstrapPayoutContext();

        $recon = Reconciliation::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantId,
            'payout_id' => $payout->id,
            'match_key' => 'order_no:TEST-1',
            'expected_total_net' => 100,
            'actual_total_net' => 80,
            'diff_total_net' => -20,
            'status' => 'mismatch',
            'run_hash' => 'run-v11',
            'run_version' => 2,
            'reconciled_at' => now(),
        ]);

        foreach ([8, 12] as $amount) {
            LossFinding::query()->withoutGlobalScope('tenant_scope')->create([
                'tenant_id' => $tenantId,
                'reconciliation_id' => $recon->id,
                'payout_id' => $payout->id,
                'code' => 'LOSS_COMMISSION_HIGH',
                'severity' => 'high',
                'amount' => $amount,
                'type' => 'commission',
                'confidence' => 88,
                'confidence_score' => 88,
            ]);
        }

        $service = app(LossPatternAggregatorService::class);
        $patterns = $service->aggregateForPayout($tenantId, (int) $payout->id, 'run-v11', 2);

        $this->assertCount(1, $patterns);
        $this->assertSame(2, $patterns[0]->occurrence_count);
    }

    public function test_regression_guard_flags_when_current_run_is_worse(): void
    {
        [$tenantId, $payout] = $this->bootstrapPayoutContext();

        Reconciliation::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantId,
            'payout_id' => $payout->id,
            'match_key' => 'order_no:OLD-1',
            'expected_total_net' => 100,
            'actual_total_net' => 95,
            'diff_total_net' => -5,
            'status' => 'warning',
            'run_hash' => 'run-old',
            'run_version' => 1,
            'reconciled_at' => now()->subHour(),
        ]);

        Reconciliation::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantId,
            'payout_id' => $payout->id,
            'match_key' => 'order_no:NEW-1',
            'expected_total_net' => 100,
            'actual_total_net' => 60,
            'diff_total_net' => -40,
            'status' => 'mismatch',
            'run_hash' => 'run-new',
            'run_version' => 2,
            'reconciled_at' => now(),
        ]);

        $service = app(ReconcileRegressionGuardService::class);
        $result = $service->evaluateAndPersist($payout->fresh(), 'run-new');

        $this->assertTrue($result['regression_flag']);
        $this->assertTrue((bool) $payout->fresh()->regression_flag);
    }

    /**
     * @return array{0:int}
     */
    private function bootstrapTenant(): array
    {
        $user = User::factory()->create([
            'role' => 'finance',
            'email_verified_at' => now(),
        ]);

        DB::table('tenants')->insert([
            'id' => $user->id,
            'name' => 'Tenant V11',
            'status' => 'active',
            'plan' => 'pro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->forceFill(['tenant_id' => $user->id])->save();

        return [(int) $user->id];
    }

    /**
     * @return array{0:int,1:Payout}
     */
    private function bootstrapPayoutContext(): array
    {
        [$tenantId] = $this->bootstrapTenant();

        $integration = MarketplaceIntegration::query()->create([
            'code' => 'trendyol',
            'name' => 'Trendyol',
            'is_enabled' => true,
        ]);

        $account = MarketplaceAccount::query()->create([
            'tenant_id' => $tenantId,
            'marketplace_integration_id' => $integration->id,
            'marketplace' => 'trendyol',
            'connector_key' => 'trendyol',
            'store_name' => 'Store',
            'credentials' => ['api_key' => 'x'],
            'status' => 'active',
        ]);

        $payout = Payout::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantId,
            'marketplace' => 'trendyol',
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $account->id,
            'account_id' => $account->id,
            'payout_reference' => 'PO-V11',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->toDateString(),
            'expected_amount' => 100,
            'paid_amount' => 90,
            'currency' => 'TRY',
            'status' => 'DISCREPANCY',
        ]);

        return [$tenantId, $payout];
    }
}
