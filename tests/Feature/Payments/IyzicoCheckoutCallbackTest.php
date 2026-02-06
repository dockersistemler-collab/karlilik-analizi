<?php

namespace Tests\Feature\Payments;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\User;
use App\Services\Payments\IyzicoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IyzicoCheckoutCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_retrieve_success_marks_purchase_paid_and_grants_module(): void
    {
        $now = Carbon::create(2026, 1, 5, 10, 0, 0, 'UTC');
        Carbon::setTestNow($now);

        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.reports',
            'name' => 'Raporlar',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $purchase = ModulePurchase::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'iyzico',
            'provider_payment_id' => null,
            'amount' => 199.90,
            'currency' => 'TRY',
            'period' => 'monthly',
            'status' => 'pending',
            'meta' => ['source' => 'test'],
        ]);

        $this->mock(IyzicoClient::class, function ($mock) use ($purchase) {
            $mock->shouldReceive('retrieveCheckoutForm')
                ->with('tok_test')
                ->andReturn([
                    'status' => 'SUCCESS',
                    'conversationId' => "purchase:{$purchase->id}",
                    'paymentId' => 'pay_test_1',
                    'raw' => ['status' => 'SUCCESS'],
                ]);
        });

        $response = $this->post(route('iyzico.callback'), ['token' => 'tok_test']);

        $response->assertRedirect(route('portal.addons.index'));

        $purchase->refresh();
        $this->assertSame('paid', $purchase->status);
        $this->assertSame('pay_test_1', $purchase->provider_payment_id);
        $this->assertNotNull($purchase->ends_at);
        $this->assertSame($now->copy()->addMonth()->toDateString(), $purchase->ends_at->toDateString());

        $this->assertDatabaseHas('user_modules', [
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => 'active',
        ]);

        // Idempotency: second callback shouldn't break anything.
        $response2 = $this->post(route('iyzico.callback'), ['token' => 'tok_test']);
        $response2->assertRedirect(route('portal.addons.index'));

        $this->assertDatabaseCount('user_modules', 1);
        $purchase->refresh();
        $this->assertSame('paid', $purchase->status);
        $this->assertSame('pay_test_1', $purchase->provider_payment_id);
    }

    public function test_callback_rejects_invalid_conversation_id_format(): void
    {
        $this->mock(IyzicoClient::class, function ($mock) {
            $mock->shouldReceive('retrieveCheckoutForm')
                ->with('tok_bad')
                ->andReturn([
                    'status' => 'SUCCESS',
                    'conversationId' => 'invalid',
                    'paymentId' => 'pay_x',
                ]);
        });

        $this->post(route('iyzico.callback'), ['token' => 'tok_bad'])->assertStatus(400);
    }

    public function test_callback_returns_404_when_purchase_not_found(): void
    {
        $this->mock(IyzicoClient::class, function ($mock) {
            $mock->shouldReceive('retrieveCheckoutForm')
                ->with('tok_missing')
                ->andReturn([
                    'status' => 'SUCCESS',
                    'conversationId' => 'purchase:999999',
                    'paymentId' => 'pay_x',
                ]);
        });

        $this->post(route('iyzico.callback'), ['token' => 'tok_missing'])->assertStatus(404);
    }

    public function test_callback_returns_409_on_provider_payment_id_mismatch(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.reports',
            'name' => 'Raporlar',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $purchase = ModulePurchase::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'iyzico',
            'provider_payment_id' => 'pay_old',
            'amount' => 199.90,
            'currency' => 'TRY',
            'period' => 'monthly',
            'status' => 'pending',
            'meta' => ['source' => 'test'],
        ]);

        $this->mock(IyzicoClient::class, function ($mock) use ($purchase) {
            $mock->shouldReceive('retrieveCheckoutForm')
                ->with('tok_mismatch')
                ->andReturn([
                    'status' => 'SUCCESS',
                    'conversationId' => "purchase:{$purchase->id}",
                    'paymentId' => 'pay_new',
                ]);
        });

        $this->post(route('iyzico.callback'), ['token' => 'tok_mismatch'])->assertStatus(409);
    }

    public function test_callback_redirects_to_api_settings_when_purchase_is_einvoice_api(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.einvoice_api',
            'name' => 'E-Fatura API Erişimi',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $purchase = ModulePurchase::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'iyzico',
            'provider_payment_id' => null,
            'amount' => 1990.00,
            'currency' => 'TRY',
            'period' => 'yearly',
            'status' => 'pending',
            'meta' => ['source' => 'test'],
        ]);

        $this->mock(IyzicoClient::class, function ($mock) use ($purchase) {
            $mock->shouldReceive('retrieveCheckoutForm')
                ->with('tok_api')
                ->andReturn([
                    'status' => 'SUCCESS',
                    'conversationId' => "purchase:{$purchase->id}",
                    'paymentId' => 'pay_api_1',
                    'raw' => ['status' => 'SUCCESS'],
                ]);
        });

        $this->post(route('iyzico.callback'), ['token' => 'tok_api'])
            ->assertRedirect(route('portal.settings.api'));
    }
}

