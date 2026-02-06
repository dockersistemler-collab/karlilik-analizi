<?php

namespace Tests\Feature;

use App\Models\BillingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingEventAdminIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_index(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $tenant = User::factory()->create(['role' => 'client']);

        BillingEvent::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'type' => 'invoice.created',
            'status' => 'success',
            'amount' => 10,
            'currency' => 'TRY',
            'provider' => 'manual',
            'correlation_id' => 'corr-123',
            'payload' => ['provider_ref' => 'INV-1'],
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.observability.billing-events.index'))
            ->assertOk()
            ->assertSee('invoice.created');
    }

    public function test_non_authorized_user_gets_403(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)
            ->get(route('super-admin.observability.billing-events.index'))
            ->assertStatus(403);
    }
}

