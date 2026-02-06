<?php

namespace Tests\Feature\Purchases;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\User;
use App\Services\Payments\IyzicoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleRenewEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_renew_creates_pending_purchase_and_renders_checkout_view(): void
    {
        config()->set('payments.mode', 'iyzico');
        config()->set('payments.iyzico_enabled', true);

        config()->set('modules.prices', [
            'feature.reports' => [
                'monthly' => 199.90,
                'yearly' => 1990.00,
            ],
        ]);

        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.reports',
            'name' => 'Raporlar',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->mock(IyzicoClient::class, function ($mock) {
            $mock->shouldReceive('initializeCheckoutForm')
                ->once()
                ->andReturn([
                    'status' => 'SUCCESS',
                    'token' => 'tok_x',
                    'checkoutFormContent' => '<div id="iyzico">ok</div>',
                    'raw' => ['status' => 'SUCCESS'],
                ]);
        });

        $response = $this->actingAs($user)->post(route('portal.my-modules.renew', $module), [
            'period' => 'monthly',
        ]);

        $response->assertOk();
        $response->assertViewIs('admin.payments.iyzico-checkout');

        $purchase = ModulePurchase::query()->latest('id')->first();
        $this->assertNotNull($purchase);
        $this->assertSame('iyzico', $purchase->provider);
        $this->assertSame('pending', $purchase->status);
        $this->assertSame('monthly', $purchase->period);
        $this->assertSame('TRY', $purchase->currency);
        $this->assertNull($purchase->starts_at);
        $this->assertNull($purchase->ends_at);
        $this->assertSame('renew', $purchase->meta['action'] ?? null);
        $this->assertSame('my_modules', $purchase->meta['source'] ?? null);
    }

    public function test_renew_initialize_failure_redirects_back_with_error_flash(): void
    {
        config()->set('payments.mode', 'iyzico');
        config()->set('payments.iyzico_enabled', true);

        config()->set('modules.prices', [
            'feature.einvoice_api' => [
                'yearly' => 1990.00,
            ],
        ]);

        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.einvoice_api',
            'name' => 'E-Fatura API Erişimi',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->mock(IyzicoClient::class, function ($mock) {
            $mock->shouldReceive('initializeCheckoutForm')
                ->once()
                ->andReturn([
                    'status' => 'FAILURE',
                    'token' => null,
                    'checkoutFormContent' => null,
                    'errorMessage' => 'Invalid request',
                    'errorCode' => '10000',
                    'raw' => ['status' => 'FAILURE'],
                ]);
        });

        $response = $this->actingAs($user)->post(route('portal.my-modules.renew', $module), [
            'period' => 'yearly',
        ]);

        $response->assertRedirect(route('portal.settings.api'));
        $response->assertSessionHas('error');

        $purchase = ModulePurchase::query()->latest('id')->first();
        $this->assertNotNull($purchase);
        $this->assertSame('iyzico', $purchase->provider);
        $this->assertSame('cancelled', $purchase->status);
    }

    public function test_renew_fake_mode_marks_paid_and_grants_module(): void
    {
        config()->set('payments.mode', 'fake');
        config()->set('payments.iyzico_enabled', false);

        config()->set('modules.prices', [
            'feature.einvoice_api' => [
                'yearly' => 1990.00,
            ],
        ]);

        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.einvoice_api',
            'name' => 'E-Fatura API Erişimi',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)
            ->from(route('portal.settings.api'))
            ->post(route('portal.my-modules.renew', $module), [
                'period' => 'yearly',
            ]);

        $response->assertRedirect(route('portal.settings.api'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('module_purchases', [
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'fake',
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('user_modules', [
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => 'active',
        ]);
    }

    public function test_renew_when_iyzico_not_configured_redirects_back_with_error(): void
    {
        config()->set('payments.mode', 'iyzico');
        config()->set('payments.iyzico_enabled', false);

        config()->set('modules.prices', [
            'feature.einvoice_api' => [
                'yearly' => 1990.00,
            ],
        ]);

        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.einvoice_api',
            'name' => 'E-Fatura API Erişimi',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->mock(IyzicoClient::class, function ($mock) {
            $mock->shouldReceive('initializeCheckoutForm')->never();
        });

        $response = $this->actingAs($user)
            ->from(route('portal.settings.api'))
            ->post(route('portal.my-modules.renew', $module), [
                'period' => 'yearly',
            ]);

        $response->assertRedirect(route('portal.settings.api'));
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('module_purchases', 0);
    }
}

