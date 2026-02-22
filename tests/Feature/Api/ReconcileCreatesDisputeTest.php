<?php

namespace Tests\Feature\Api;

use App\Domains\Reconciliation\Actions\ReconcilePayoutsAction;
use App\Domains\Settlements\Models\Dispute;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\SettlementRule;
use App\Models\MarketplaceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReconcileCreatesDisputeTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconcile_opens_dispute_on_difference(): void
    {
        $tenantOwner = User::factory()->create(['role' => 'client', 'email' => 'owner-reconcile@test.local']);
        DB::table('tenants')->insert([
            'id' => $tenantOwner->id,
            'name' => 'Tenant Reconcile',
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
            'ruleset' => ['tolerances' => ['amount' => 1.00, 'percent' => 0.5]],
        ]);

        $payout = Payout::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantOwner->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $account->id,
            'payout_reference' => 'PO-001',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->toDateString(),
            'expected_amount' => 200,
            'paid_amount' => 150,
            'paid_date' => now()->toDateString(),
            'currency' => 'TRY',
            'status' => 'EXPECTED',
        ]);

        app(ReconcilePayoutsAction::class)->execute($account->id);

        $this->assertDatabaseHas('disputes', [
            'tenant_id' => $tenantOwner->id,
            'payout_id' => $payout->id,
            'status' => 'OPEN',
        ]);
        $this->assertEquals('DISCREPANCY', $payout->fresh()->status);

        $dispute = Dispute::query()->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantOwner->id)
            ->where('payout_id', $payout->id)
            ->first();
        $this->assertNotNull($dispute);
        $this->assertEquals(50.0, abs((float) $dispute->diff_amount));
    }
}
