<?php

namespace Tests\Feature\Api;

use App\Domains\Settlements\Models\FeatureFlag;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\Reconciliation;
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

        foreach (['payouts.view', 'payouts.reconcile', 'exports.create', 'disputes.manage', 'dashboard.view'] as $permission) {
            Permission::findOrCreate($permission, 'sanctum');
        }
        $user->syncPermissions(['payouts.view', 'payouts.reconcile', 'exports.create', 'disputes.manage', 'dashboard.view']);

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
