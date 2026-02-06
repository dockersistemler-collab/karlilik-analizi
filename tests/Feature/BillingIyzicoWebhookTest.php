<?php

namespace Tests\Feature;

use App\Models\BillingCheckout;
use App\Models\NotificationAuditLog;
use App\Models\User;
use App\Services\Billing\Iyzico\CheckoutStatusService;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingIyzicoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_is_idempotent_and_updates_plan(): void
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
            'provider_session_id' => 'tok-abc',
            'provider_token' => 'tok-abc',
        ]);

        app(SettingsRepository::class)->set('billing', 'iyzico.webhook_secret', 'secret123', true);

        $mock = $this->createMock(\Iyzipay\Model\CheckoutForm::class);
        $mock->method('getStatus')->willReturn('success');
        $mock->method('getPaymentStatus')->willReturn('SUCCESS');
        $mock->method('getPaidPrice')->willReturn('999');

        $service = $this->createMock(CheckoutStatusService::class);
        $service->method('retrieve')->with('tok-abc')->willReturn($mock);
        $this->app->instance(CheckoutStatusService::class, $service);

        $payload = ['token' => 'tok-abc'];
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'secret123');

        $this->call(
            'POST',
            route('billing.iyzico.webhook'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_IYZ_SIGNATURE_V3' => $signature],
            $body
        )->assertOk();

        $checkout->refresh();
        $this->assertSame('completed', $checkout->status);

        $countBefore = NotificationAuditLog::query()
            ->where('tenant_id', $user->id)
            ->where('action', 'billing_payment_completed')
            ->count();

        $this->call(
            'POST',
            route('billing.iyzico.webhook'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_IYZ_SIGNATURE_V3' => $signature],
            $body
        )->assertOk();

        $countAfter = NotificationAuditLog::query()
            ->where('tenant_id', $user->id)
            ->where('action', 'billing_payment_completed')
            ->count();

        $this->assertSame($countBefore, $countAfter);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        app(SettingsRepository::class)->set('billing', 'iyzico.webhook_secret', 'secret123', true);

        $this->withHeader('X-IYZ-SIGNATURE-V3', 'bad-signature')
            ->post(route('billing.iyzico.webhook'), ['token' => 'tok-abc'], ['CONTENT_TYPE' => 'application/json'])
            ->assertStatus(401);
    }
}
