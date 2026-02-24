<?php

namespace Tests\Feature\Api;

use App\Domains\Disputes\Actions\DetectAnomaliesAction;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\PayoutTransaction;
use App\Domains\Settlements\Models\SettlementRule;
use App\Models\MarketplaceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DisputeTypeClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispute_type_is_classified_as_commission_diff(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        DB::table('tenants')->insert([
            'id' => $user->id,
            'name' => 'Tenant Classification',
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
            'tenant_id' => $user->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace' => 'trendyol',
            'connector_key' => 'trendyol',
            'store_name' => 'Store',
            'credentials' => ['api_key' => 'x'],
            'status' => 'active',
        ]);

        SettlementRule::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'marketplace_integration_id' => $integration->id,
            'ruleset' => ['tolerances' => ['amount' => 0.5, 'percent' => 0.1]],
        ]);

        $payout = Payout::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $account->id,
            'payout_reference' => 'PO-COMM-1',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->toDateString(),
            'expected_amount' => 100,
            'paid_amount' => 90,
            'paid_date' => now()->toDateString(),
            'currency' => 'TRY',
            'status' => 'DISCREPANCY',
        ]);

        PayoutTransaction::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $user->id,
            'payout_id' => $payout->id,
            'type' => 'COMMISSION',
            'reference_id' => 'TX-1',
            'amount' => -10,
            'vat_amount' => 0,
            'meta' => [],
            'raw_payload' => [],
        ]);

        app(DetectAnomaliesAction::class)->execute($payout->id);

        $this->assertDatabaseHas('disputes', [
            'tenant_id' => $user->id,
            'payout_id' => $payout->id,
            'dispute_type' => 'COMMISSION_DIFF',
            'status' => 'OPEN',
        ]);
    }
}
