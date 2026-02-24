<?php

namespace Tests\Feature\Settlements;

use App\Domains\Settlements\Models\Dispute;
use App\Domains\Settlements\Models\FeatureFlag;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\PayoutTransaction;
use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Jobs\GenerateSettlementExportJob;
use App\Jobs\ReconcileSinglePayoutJob;
use App\Models\MarketplaceAccount;
use App\Models\Module;
use App\Models\Plan;
use App\Models\SubUser;
use App\Models\SubUserPermission;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PortalSettlementCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_settlement_pages_render_for_entitled_client(): void
    {
        [$user, $payout] = $this->bootstrapSettlementContext();

        Dispute::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'payout_id' => $payout->id,
            'dispute_type' => 'UNKNOWN_DEDUCTION',
            'expected_amount' => 100,
            'actual_amount' => 90,
            'diff_amount' => 10,
            'status' => 'OPEN',
        ]);

        $this->actingAs($user)
            ->get(route('portal.settlements.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('portal.settlements.show', $payout->id))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('portal.settlements.disputes'))
            ->assertOk();
    }

    public function test_module_off_redirects_to_upsell(): void
    {
        [$user] = $this->bootstrapSettlementContext(moduleActive: false);

        $this->actingAs($user)
            ->get(route('portal.settlements.index'))
            ->assertRedirect(route('portal.modules.upsell', ['code' => 'feature.hakedis']));
    }

    public function test_reconcile_on_portal_updates_only_selected_payout(): void
    {
        [$user, $firstPayout, $secondPayout] = $this->bootstrapSettlementContext(withSecondPayout: true);
        Queue::fake();

        $this->actingAs($user)
            ->post(route('portal.settlements.reconcile', $firstPayout->id))
            ->assertRedirect(route('portal.settlements.show', $firstPayout->id));

        Queue::assertPushed(ReconcileSinglePayoutJob::class, function (ReconcileSinglePayoutJob $job) use ($user, $firstPayout): bool {
            return $job->tenantId === (int) $user->id && $job->payoutId === (int) $firstPayout->id;
        });
        Queue::assertNotPushed(ReconcileSinglePayoutJob::class, function (ReconcileSinglePayoutJob $job) use ($secondPayout): bool {
            return $job->payoutId === (int) $secondPayout->id;
        });
    }

    public function test_large_export_is_queued_async(): void
    {
        [$user, $payout] = $this->bootstrapSettlementContext();
        Queue::fake();

        foreach (range(1, 1001) as $i) {
            PayoutTransaction::query()->withoutGlobalScope('tenant_scope')->create([
                'tenant_id' => $user->id,
                'payout_id' => $payout->id,
                'type' => 'COMMISSION',
                'reference_id' => 'TX-'.$i,
                'amount' => -1,
                'vat_amount' => 0,
                'meta' => [],
                'raw_payload' => [],
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('portal.settlements.export', $payout->id));

        $response->assertRedirect();
        Queue::assertPushed(GenerateSettlementExportJob::class, function (GenerateSettlementExportJob $job) use ($user, $payout): bool {
            return $job->tenantId === (int) $user->id && $job->payoutId === (int) $payout->id;
        });
    }

    public function test_subuser_view_permission_cannot_manage_settlements(): void
    {
        $this->withoutMiddleware([EnsureActiveSubscription::class, EnsureModuleEnabled::class]);

        [$user, $payout] = $this->bootstrapSettlementContext();

        $subUser = SubUser::query()->create([
            'owner_user_id' => $user->id,
            'name' => 'Sub User',
            'email' => 'sub-settlement@test.local',
            'password' => 'password123',
            'is_active' => true,
        ]);
        SubUserPermission::query()->create([
            'sub_user_id' => $subUser->id,
            'permission_key' => 'settlements.view',
        ]);

        $this->actingAs($subUser, 'subuser')
            ->post(route('portal.settlements.reconcile', $payout->id))
            ->assertForbidden();
    }

    public function test_dispute_status_can_be_updated_from_portal(): void
    {
        [$user, $payout] = $this->bootstrapSettlementContext();

        $dispute = Dispute::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'payout_id' => $payout->id,
            'dispute_type' => 'UNKNOWN_DEDUCTION',
            'expected_amount' => 100,
            'actual_amount' => 90,
            'diff_amount' => 10,
            'status' => 'OPEN',
        ]);

        $this->actingAs($user)
            ->patch(route('portal.settlements.disputes.update', $dispute->id), [
                'status' => 'RESOLVED',
            ])
            ->assertRedirect(route('portal.settlements.disputes'));

        $this->assertDatabaseHas('disputes', [
            'id' => $dispute->id,
            'status' => 'RESOLVED',
        ]);
    }

    private function bootstrapSettlementContext(bool $moduleActive = true, bool $withSecondPayout = false): array
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        DB::table('tenants')->insert([
            'id' => $user->id,
            'name' => 'Tenant '.$user->id,
            'status' => 'active',
            'plan' => 'starter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Module::query()->create([
            'code' => 'feature.hakedis',
            'name' => 'Hakediş Kontrol Merkezi',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => $moduleActive,
            'sort_order' => 0,
        ]);

        $plan = Plan::query()->create([
            'name' => 'Settlement Plan',
            'slug' => 'settlement-plan-'.$user->id,
            'price' => 0,
            'yearly_price' => 0,
            'billing_period' => 'monthly',
            'max_products' => 0,
            'max_marketplaces' => 0,
            'max_orders_per_month' => 0,
            'max_tickets_per_month' => 0,
            'api_access' => false,
            'advanced_reports' => false,
            'priority_support' => false,
            'custom_integrations' => false,
            'features' => ['modules' => ['feature.hakedis']],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'amount' => 0,
            'billing_period' => 'monthly',
            'auto_renew' => true,
            'usage_reset_at' => now()->addDays(30),
        ]);

        FeatureFlag::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'key' => 'hakedis_module',
            'enabled' => true,
        ]);

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

        $payout = Payout::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $account->id,
            'payout_reference' => 'PO-100',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->toDateString(),
            'expected_amount' => 100,
            'paid_amount' => 90,
            'paid_date' => now()->toDateString(),
            'currency' => 'TRY',
            'status' => 'DISCREPANCY',
        ]);

        if (!$withSecondPayout) {
            return [$user, $payout];
        }

        $payout2 = Payout::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $account->id,
            'payout_reference' => 'PO-200',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->toDateString(),
            'expected_amount' => 80,
            'paid_amount' => 60,
            'paid_date' => now()->toDateString(),
            'currency' => 'TRY',
            'status' => 'DISCREPANCY',
        ]);

        return [$user, $payout, $payout2];
    }
}
