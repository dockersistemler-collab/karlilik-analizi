<?php

namespace Tests\Feature\Api;

use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\Payout;
use App\Models\MarketplaceAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TenantIsolationPayoutsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_only_sees_its_own_payouts(): void
    {
        $tenantOwnerA = User::factory()->create(['role' => 'client', 'email' => 'owner-a@test.local']);
        $tenantOwnerB = User::factory()->create(['role' => 'client', 'email' => 'owner-b@test.local']);
        DB::table('tenants')->insert([
            ['id' => $tenantOwnerA->id, 'name' => 'Tenant A', 'status' => 'active', 'plan' => 'pro', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $tenantOwnerB->id, 'name' => 'Tenant B', 'status' => 'active', 'plan' => 'pro', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $integration = MarketplaceIntegration::query()->create([
            'code' => 'trendyol',
            'name' => 'Trendyol',
            'is_enabled' => true,
        ]);

        $accountA = MarketplaceAccount::query()->create([
            'tenant_id' => $tenantOwnerA->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace' => 'trendyol',
            'connector_key' => 'trendyol',
            'store_name' => 'A Store',
            'credentials' => ['api_key' => 'a'],
            'status' => 'active',
        ]);
        $accountB = MarketplaceAccount::query()->create([
            'tenant_id' => $tenantOwnerB->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace' => 'trendyol',
            'connector_key' => 'trendyol',
            'store_name' => 'B Store',
            'credentials' => ['api_key' => 'b'],
            'status' => 'active',
        ]);

        Payout::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantOwnerA->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $accountA->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->toDateString(),
            'expected_amount' => 100,
            'currency' => 'TRY',
            'status' => 'EXPECTED',
        ]);
        Payout::query()->withoutGlobalScope('tenant_scope')->create([
            'tenant_id' => $tenantOwnerB->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $accountB->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->toDateString(),
            'expected_amount' => 200,
            'currency' => 'TRY',
            'status' => 'EXPECTED',
        ]);

        $tenantAdminA = User::factory()->create([
            'tenant_id' => $tenantOwnerA->id,
            'role' => 'tenant_admin',
            'email' => 'tenant-a@test.local',
        ]);
        $tenantAdminA->givePermissionTo(
            Permission::findOrCreate('payouts.view', 'sanctum')
        );
        DB::table('feature_flags')->insert([
            'tenant_id' => $tenantOwnerA->id,
            'key' => 'hakedis_module',
            'enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($tenantAdminA);

        $response = $this->getJson('/api/v1/payouts');
        $response->assertOk();
        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        $this->assertEquals($tenantOwnerA->id, $items[0]['tenant_id']);
    }
}
