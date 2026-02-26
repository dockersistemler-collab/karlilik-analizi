<?php

namespace Tests\Feature\Api;

use App\Domains\Settlements\Models\FeatureFlag;
use App\Domains\Settlements\Models\Dispute;
use App\Domains\Settlements\Models\LossFinding;
use App\Domains\Settlements\Models\LossPattern;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\Reconciliation;
use App\Domains\Settlements\Models\ReconciliationRule;
use App\Jobs\ReconcileSinglePayoutJob;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LossFinderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_off_blocks_loss_finder_endpoints(): void
    {
        [$user, $payout] = $this->bootstrapContext(enableModule: false);

        Sanctum::actingAs($user);
        $this->getJson("/api/v1/payouts/{$payout->id}/summary")->assertForbidden();
    }

    public function test_import_and_reconcile_queue_flow(): void
    {
        [$user, $payout, $account] = $this->bootstrapContext();
        Queue::fake();

        Sanctum::actingAs($user);

        $csv = implode("\n", [
            'order_no,type,gross_amount,vat_amount,net_amount,currency',
            'ORD-API-1,sale,120,20,100,TRY',
            'ORD-API-1,commission,-18,-3,-15,TRY',
        ]);

        $file = UploadedFile::fake()->createWithContent('settlement.csv', $csv);
        $response = $this->postJson('/api/v1/payouts/import', [
            'file' => $file,
            'account_id' => $account->id,
            'marketplace' => 'trendyol',
        ]);
        $response->assertCreated();
        $importedPayoutId = (int) $response->json('data.payout_id');

        $this->postJson("/api/v1/payouts/{$importedPayoutId}/reconcile", [
            'tolerance' => 0.01,
        ])->assertOk()->assertJsonPath('data.queued', true);

        Queue::assertPushed(ReconcileSinglePayoutJob::class, function (ReconcileSinglePayoutJob $job) use ($user, $importedPayoutId): bool {
            return $job->tenantId === (int) $user->id && $job->payoutId === $importedPayoutId;
        });

        Reconciliation::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'payout_id' => $payout->id,
            'match_key' => 'order_no:ORD-API-1',
            'expected_total_net' => 100,
            'actual_total_net' => 90,
            'diff_total_net' => -10,
            'diff_breakdown_json' => ['commission' => ['expected_net' => -10, 'actual_net' => -20, 'diff_net' => -10]],
            'loss_findings_json' => [['code' => 'LOSS_COMMISSION_HIGH', 'amount' => 10]],
            'status' => 'mismatch',
            'tolerance_used' => 0.01,
            'reconciled_at' => now(),
        ]);

        $this->getJson("/api/v1/payouts/{$payout->id}/summary")
            ->assertOk()
            ->assertJsonPath('data.totals.diff_total_net', -10);
    }

    public function test_export_and_dispute_bulk_create_and_status_update(): void
    {
        [$user, $payout] = $this->bootstrapContext();
        Sanctum::actingAs($user);

        $reconciliation = Reconciliation::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'payout_id' => $payout->id,
            'match_key' => 'order_no:ORD-API-2',
            'expected_total_net' => 120,
            'actual_total_net' => 90,
            'diff_total_net' => -30,
            'diff_breakdown_json' => ['commission' => ['expected_net' => -10, 'actual_net' => -40, 'diff_net' => -30]],
            'loss_findings_json' => [[
                'code' => 'LOSS_COMMISSION_HIGH',
                'detail' => 'high commission',
                'severity' => 'high',
                'amount' => 30,
                'type' => 'commission',
                'suggested_dispute_type' => 'COMMISSION_DIFF',
            ]],
            'status' => 'mismatch',
            'tolerance_used' => 0.01,
            'reconciled_at' => now(),
        ]);

        $this->postJson("/api/v1/payouts/{$payout->id}/export?format=csv")
            ->assertOk();

        $create = $this->postJson('/api/v1/disputes/from-findings', [
            'reconciliation_ids' => [$reconciliation->id],
        ])->assertCreated();

        $disputeId = (int) $create->json('data.dispute_ids.0');

        $this->patchJson("/api/v1/disputes/{$disputeId}", [
            'status' => 'resolved',
            'notes' => 'resolved by test',
        ])->assertOk()->assertJsonPath('data.status', 'resolved');
    }

    public function test_v11_patterns_findings_evidence_pack_regression_and_tenant_rule_endpoints(): void
    {
        [$user, $payout] = $this->bootstrapContext();
        Sanctum::actingAs($user);

        $reconciliation = Reconciliation::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'payout_id' => $payout->id,
            'match_key' => 'order_no:ORD-API-V11',
            'expected_total_net' => 150,
            'actual_total_net' => 100,
            'diff_total_net' => -50,
            'loss_findings_json' => [[
                'code' => 'LOSS_COMMISSION_HIGH',
                'severity' => 'high',
                'amount' => 50,
                'confidence_score' => 92,
            ]],
            'findings_summary_json' => ['count' => 1],
            'run_hash' => 'run-v11-api',
            'run_version' => 2,
            'status' => 'mismatch',
            'tolerance_used' => 0.01,
            'reconciled_at' => now(),
        ]);

        LossFinding::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'reconciliation_id' => $reconciliation->id,
            'payout_id' => $payout->id,
            'code' => 'LOSS_COMMISSION_HIGH',
            'severity' => 'high',
            'amount' => 50,
            'type' => 'commission',
            'confidence' => 92,
            'confidence_score' => 92,
        ]);

        LossPattern::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'payout_id' => $payout->id,
            'run_hash' => 'run-v11-api',
            'run_version' => 2,
            'pattern_key' => sha1($user->id.'|trendyol|LOSS_COMMISSION_HIGH|commission|'),
            'finding_code' => 'LOSS_COMMISSION_HIGH',
            'code' => 'LOSS_COMMISSION_HIGH',
            'type' => 'commission',
            'severity' => 'high',
            'occurrence_count' => 1,
            'occurrences' => 1,
            'total_amount' => 50,
            'avg_confidence' => 92,
        ]);

        $this->getJson("/api/v1/payouts/{$payout->id}/findings?min_confidence=80")
            ->assertOk()
            ->assertJsonPath('data.data.0.code', 'LOSS_COMMISSION_HIGH');

        $this->getJson("/api/v1/payouts/{$payout->id}/patterns")
            ->assertOk()
            ->assertJsonPath('data.0.code', 'LOSS_COMMISSION_HIGH');

        $this->putJson('/api/v1/reconciliation-rules/tenant-override', [
            'marketplace' => 'trendyol',
            'rule_type' => 'tolerance',
            'key' => 'default',
            'value' => ['amount' => 0.07],
            'priority' => 50,
            'is_active' => true,
        ])->assertOk();

        $this->assertDatabaseHas('reconciliation_rules', [
            'tenant_id' => $user->id,
            'scope_type' => 'tenant',
            'marketplace' => 'trendyol',
            'rule_type' => 'tolerance',
            'key' => 'default',
        ]);

        $dispute = Dispute::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'payout_id' => $payout->id,
            'dispute_type' => 'COMMISSION_DIFF',
            'status' => 'OPEN',
            'amount' => 50,
            'expected_amount' => 150,
            'actual_amount' => 100,
            'diff_amount' => 50,
            'evidence_json' => ['code' => 'LOSS_COMMISSION_HIGH'],
        ]);

        $this->postJson("/api/v1/disputes/{$dispute->id}/evidence-pack")
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'queued');

        $this->getJson("/api/v1/disputes/{$dispute->id}/evidence-pack")
            ->assertOk()
            ->assertJsonPath('data.dispute_id', $dispute->id)
            ->assertJsonStructure(['data' => ['path', 'meta']]);

        $this->getJson("/api/v1/payouts/{$payout->id}/regression")
            ->assertOk()
            ->assertJsonStructure(['data' => ['regression_flag', 'regression_note']]);
    }

    private function bootstrapContext(bool $enableModule = true): array
    {
        $user = User::factory()->create([
            'role' => 'finance',
            'email_verified_at' => now(),
        ]);

        DB::table('tenants')->insert([
            'id' => $user->id,
            'name' => 'Tenant API',
            'status' => 'active',
            'plan' => 'pro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->forceFill(['tenant_id' => $user->id])->save();

        if ($enableModule) {
            FeatureFlag::query()->withoutGlobalScope('tenant_scope')->create([
                'tenant_id' => $user->id,
                'key' => 'hakedis_module',
                'enabled' => true,
            ]);
        }

        foreach (['payouts.view', 'payouts.reconcile', 'exports.create', 'disputes.manage', 'disputes.view', 'dashboard.view', 'settlement_rules.manage'] as $permission) {
            Permission::findOrCreate($permission, 'sanctum');
        }
        $user->syncPermissions(['payouts.view', 'payouts.reconcile', 'exports.create', 'disputes.manage', 'disputes.view', 'dashboard.view', 'settlement_rules.manage']);

        $integration = MarketplaceIntegration::query()->create([
            'code' => 'trendyol',
            'name' => 'Trendyol',
            'is_enabled' => true,
        ]);

        $account = MarketplaceAccount::query()->create([
            'tenant_id' => $user->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace' => 'trendyol',
            'connector_key' => 'trendyol',
            'store_name' => 'Store',
            'credentials' => ['api_key' => 'x'],
            'status' => 'active',
        ]);

        $order = Order::query()->create([
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $account->id,
            'marketplace_order_id' => 'MP-ORD-API-1',
            'order_number' => 'ORD-API-1',
            'status' => 'delivered',
            'total_amount' => 120,
            'commission_amount' => 20,
            'net_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'totals' => ['vat_total' => 20, 'commission_vat' => 4],
            'order_date' => now(),
            'items' => [],
        ]);

        $payout = Payout::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'marketplace' => 'trendyol',
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $account->id,
            'account_id' => $account->id,
            'payout_reference' => 'PO-API-1',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->toDateString(),
            'expected_amount' => 100,
            'paid_amount' => 90,
            'currency' => 'TRY',
            'status' => 'DISCREPANCY',
        ]);

        return [$user, $payout, $account];
    }
}
