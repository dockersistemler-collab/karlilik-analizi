<?php

namespace Tests\Feature;

use App\Models\BillingCheckout;
use App\Models\NotificationAuditLog;
use App\Models\User;
use App\Services\Billing\Iyzico\CheckoutStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingIyzicoCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_completes_checkout_and_updates_plan(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $checkout = BillingCheckout::create([
            'tenant_id' => $user->id,
            'plan_code' => 'pro',
            'status' => 'pending',
            'provider' => 'iyzico',
            'provider_session_id' => 'tok-123',
            'provider_token' => 'tok-123',
        ]);

        $mock = $this->createMock(\Iyzipay\Model\CheckoutForm::class);
        $mock->method('getStatus')->willReturn('success');
        $mock->method('getPaymentStatus')->willReturn('SUCCESS');
        $mock->method('getPaidPrice')->willReturn('999');

        $service = $this->createMock(CheckoutStatusService::class);
        $service->method('retrieve')->with('tok-123')->willReturn($mock);
        $this->app->instance(CheckoutStatusService::class, $service);

        $response = $this->post(route('billing.iyzico.callback'), [
            'token' => 'tok-123',
        ]);

        $response->assertRedirect(route('portal.billing.success', ['checkout' => $checkout->id]));

        $checkout->refresh();
        $this->assertSame('completed', $checkout->status);
        $this->assertNotNull($checkout->completed_at);

        $user->refresh();
        $this->assertSame('pro', $user->plan_code);

        $this->assertDatabaseHas('notification_audit_logs', [
            'tenant_id' => $user->id,
            'action' => 'billing_payment_completed',
        ]);
    }
}

