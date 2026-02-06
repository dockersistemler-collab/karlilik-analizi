<?php

namespace Tests\Feature;

use App\Models\BillingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingEventActionsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_actions_show_for_allowlisted_event(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $tenant = User::factory()->create(['role' => 'client']);

        $event = BillingEvent::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'type' => 'iyzico.webhook.succeeded',
            'status' => 'success',
            'provider' => 'iyzico',
            'payload' => ['provider_payment_id' => 'p-123'],
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.observability.billing-events.show', $event))
            ->assertOk()
            ->assertSee("Webhook'u tekrar isle");
    }

    public function test_actions_hidden_for_non_allowlisted_event(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $tenant = User::factory()->create(['role' => 'client']);

        $event = BillingEvent::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'type' => 'custom.event',
            'status' => 'success',
            'provider' => 'manual',
            'payload' => ['foo' => 'bar'],
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.observability.billing-events.show', $event))
            ->assertOk()
            ->assertDontSee("Webhook'u tekrar isle")
            ->assertDontSee("Job'u tekrar calistir");
    }
}

