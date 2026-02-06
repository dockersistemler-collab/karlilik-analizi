<?php

namespace Tests\Feature;

use App\Models\BillingCheckout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IyzicoCardUpdateCallbackDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_duplicate_is_ignored_when_already_final(): void
    {
        $completedAt = now()->subDay();
        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $checkout = BillingCheckout::create([
            'tenant_id' => $tenant->id,
            'plan_code' => 'pro',
            'purpose' => 'card_update',
            'status' => 'completed',
            'provider' => 'iyzico',
            'provider_session_id' => 'state-dup',
            'provider_token' => 'tok-dup',
            'completed_at' => $completedAt,
        ]);

        $response = $this->post(route('billing.iyzico.subscription.card-update.callback'), [
            'state' => 'state-dup',
            'token' => 'tok-dup',
            'status' => 'success',
        ]);

        $response->assertOk();

        $checkout->refresh();
        $this->assertSame('completed', $checkout->status);
        $this->assertSame($completedAt->toDateTimeString(), $checkout->completed_at?->toDateTimeString());
        $this->assertNull($checkout->raw_callback);

        $this->assertDatabaseHas('billing_events', [
            'type' => 'card_update.callback_duplicate',
            'status' => 'ignored',
            'provider' => 'iyzico',
        ]);
    }
}
