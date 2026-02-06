<?php

namespace Tests\Feature;

use App\Models\BillingCheckout;
use App\Models\User;
use App\Services\Billing\Iyzico\CheckoutFormService;
use App\Services\Billing\Iyzico\CheckoutInitResult;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingIyzicoInitTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_initializes_iyzico_when_enabled(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        app(SettingsRepository::class)->set('billing', 'plans_catalog', json_encode([
            'pro' => [
                'name' => 'Pro',
                'price_monthly' => 999,
                'features' => ['health_dashboard'],
                'recommended' => true,
                'contact_sales' => false,
            ],
        ]));

        app(SettingsRepository::class)->set('billing', 'iyzico.enabled', true);

        $fake = new class extends CheckoutFormService {
            public function __construct() {}
            public function initializeCheckout(\App\Models\BillingCheckout $checkout, \App\Models\User $user, \App\Models\User $tenant, array $plan): CheckoutInitResult
            {
                return new CheckoutInitResult('tok-123', '<iframe>test</iframe>');
            }
        };

        $this->app->instance(CheckoutFormService::class, $fake);

        $response = $this->actingAs($user)
            ->post(route('portal.billing.checkout'), ['plan_code' => 'pro']);

        $checkout = BillingCheckout::query()->first();

        $response->assertRedirect(route('portal.billing.iyzico.show', $checkout));

        $this->assertDatabaseHas('billing_checkouts', [
            'id' => $checkout->id,
            'provider' => 'iyzico',
            'provider_session_id' => 'tok-123',
        ]);

        $this->actingAs($user)
            ->get(route('portal.billing.iyzico.show', $checkout))
            ->assertOk()
            ->assertSee('iframe', false);
    }
}

