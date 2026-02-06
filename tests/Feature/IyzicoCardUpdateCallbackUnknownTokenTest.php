<?php

namespace Tests\Feature;

use App\Models\BillingCheckout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IyzicoCardUpdateCallbackUnknownTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_with_unknown_token_is_ignored_and_logged(): void
    {
        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $checkout = BillingCheckout::create([
            'tenant_id' => $tenant->id,
            'plan_code' => 'pro',
            'purpose' => 'card_update',
            'status' => 'pending',
            'provider' => 'iyzico',
            'provider_session_id' => 'state-known',
            'provider_token' => 'tok-known',
        ]);

        $response = $this->post(route('billing.iyzico.subscription.card-update.callback'), [
            'state' => 'state-known',
            'token' => 'tok-unknown',
        ]);

        $response->assertOk();

        $checkout->refresh();
        $this->assertSame('pending', $checkout->status);
        $this->assertNull($checkout->raw_callback);

        $this->assertDatabaseHas('billing_events', [
            'type' => 'card_update.callback_unknown_token',
            'status' => 'ignored',
            'provider' => 'iyzico',
            'tenant_id' => null,
        ]);
    }
}
