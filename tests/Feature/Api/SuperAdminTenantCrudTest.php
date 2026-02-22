<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SuperAdminTenantCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_manage_tenants(): void
    {
        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'role' => 'super_admin',
            'email' => 'superadmin-crud@test.local',
        ]);
        $superAdmin->givePermissionTo(Permission::findOrCreate('tenants.manage', 'sanctum'));

        Sanctum::actingAs($superAdmin);

        $create = $this->postJson('/api/v1/tenants', [
            'name' => 'Tenant CRUD',
            'status' => 'active',
            'plan' => 'pro',
        ])->assertCreated();

        $tenantId = $create->json('data.id');

        $this->putJson("/api/v1/tenants/{$tenantId}", [
            'name' => 'Tenant CRUD Updated',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Tenant CRUD Updated');

        $this->deleteJson("/api/v1/tenants/{$tenantId}")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);
    }
}

