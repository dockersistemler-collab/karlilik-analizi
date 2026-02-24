<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettlementModuleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_settlement_management_screen(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'name' => 'Client A',
            'email' => 'client-a@example.com',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.settlements.index'))
            ->assertOk()
            ->assertSee('Hakediş Yönetimi');
    }

    public function test_super_admin_can_enable_settlement_visibility_for_client(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $client = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.settlements.visibility', $client), [
                'visible' => 1,
            ])
            ->assertRedirect();

        $module = Module::query()->where('code', 'feature.hakedis')->first();
        $this->assertNotNull($module);

        $tenantId = (int) ($client->tenant_id ?: $client->id);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenantId,
        ]);

        $this->assertDatabaseHas('feature_flags', [
            'tenant_id' => $tenantId,
            'key' => 'hakedis_module',
            'enabled' => 1,
        ]);

        $this->assertDatabaseMissing('user_modules', [
            'user_id' => $client->id,
            'module_id' => $module->id,
        ]);
    }

    public function test_super_admin_can_disable_settlement_visibility_for_client(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $client = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $tenantId = (int) ($client->tenant_id ?: $client->id);

        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant Test',
            'status' => 'active',
            'plan' => 'starter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('feature_flags')->insert([
            'tenant_id' => $tenantId,
            'key' => 'hakedis_module',
            'enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.settlements.visibility', $client), [
                'visible' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('feature_flags', [
            'tenant_id' => $tenantId,
            'key' => 'hakedis_module',
            'enabled' => 0,
        ]);
    }
}
