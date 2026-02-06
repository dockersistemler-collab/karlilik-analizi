<?php

namespace Tests\Feature;

use App\Models\BillingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingEventFlowLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_flow_link_points_to_correlation_filter(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $tenant = User::factory()->create(['role' => 'client']);

        $event = BillingEvent::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'type' => 'invoice.created',
            'status' => 'success',
            'provider' => 'manual',
            'correlation_id' => 'corr-flow-1',
            'payload' => ['provider_ref' => 'INV-1'],
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.observability.billing-events.show', $event))
            ->assertOk()
            ->assertSee(route('super-admin.observability.billing-events.index', [
                'correlation_id' => 'corr-flow-1',
            ]), false);
    }
}

