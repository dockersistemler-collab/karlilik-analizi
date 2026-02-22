<?php

namespace Tests\Feature\Api;

use App\Domains\Settlements\Models\FeatureFlag;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\Payout;
use App\Models\MarketplaceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_reconcile_but_finance_can(): void
    {
        $tenantOwner = User::factory()->create(['role' => 'client', 'email' => 'owner-perm@test.local']);
        DB::table('tenants')->insert([
            'id' => $tenantOwner->id,
            'name' => 'Tenant Perm',
            'status' => 'active',
            'plan' => 'pro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        FeatureFlag::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantOwner->id,
            'key' => 'hakedis_module',
            'enabled' => true,
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
        $payout = Payout::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantOwner->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $account->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->toDateString(),
            'expected_amount' => 100,
            'paid_amount' => 90,
            'currency' => 'TRY',
            'status' => 'EXPECTED',
        ]);

        $viewer = User::factory()->create([
            'tenant_id' => $tenantOwner->id,
            'role' => 'viewer',
            'email' => 'viewer@test.local',
        ]);
        $finance = User::factory()->create([
            'tenant_id' => $tenantOwner->id,
            'role' => 'finance',
            'email' => 'finance@test.local',
        ]);

        $payoutsReconcile = Permission::findOrCreate('payouts.reconcile', 'sanctum');
        $viewer->syncPermissions([]);
        $finance->syncPermissions([$payoutsReconcile]);

        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/payouts/{$payout->id}/reconcile")
            ->assertForbidden();

        Sanctum::actingAs($finance);
        $this->postJson("/api/v1/payouts/{$payout->id}/reconcile")
            ->assertOk();
    }
}

