<?php

namespace Tests\Feature;

use App\Models\BillingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingEventAdminFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_work_for_type_status_and_correlation(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $tenant = User::factory()->create(['role' => 'client']);

        BillingEvent::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'type' => 'dunning.retry_scheduled',
            'status' => 'scheduled',
            'provider' => 'iyzico',
            'correlation_id' => 'corr-aaa',
            'payload' => ['provider_ref' => 'REF-AAA'],
        ]);

        BillingEvent::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'type' => 'dunning.retry_failed',
            'status' => 'failed',
            'provider' => 'iyzico',
            'correlation_id' => 'corr-bbb',
            'payload' => ['provider_ref' => 'REF-BBB'],
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.observability.billing-events.index', [
                'type' => 'dunning.retry_failed',
                'status' => 'failed',
                'correlation_id' => 'corr-bbb',
            ]))
            ->assertOk()
            ->assertSee('dunning.retry_failed')
            ->assertDontSee('dunning.retry_scheduled');
    }
}

