<?php

namespace Tests\Feature;

use App\Models\BillingSubscription;
use App\Models\BillingSubscriptionEvent;
use App\Models\User;
use App\Services\Billing\Iyzico\Subscription\IyzicoSubscriptionClient;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionWebhookSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_webhook_is_idempotent_and_updates_plan(): void
    {
        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $subscription = BillingSubscription::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'provider' => 'iyzico',
            'plan_code' => 'pro',
            'status' => 'PENDING',
            'iyzico_subscription_reference_code' => 'sub-ref-1',
        ]);

        app(SettingsRepository::class)->set('billing', 'iyzico.webhook_secret', 'secret123', true);

        $fakeClient = $this->createMock(IyzicoSubscriptionClient::class);
        $details = new \Iyzipay\Model\Subscription\SubscriptionDetails();
        $details->setSubscriptionStatus('ACTIVE');
        $details->setPricingPlanReferenceCode('plan-1');
        $details->setStartDate(now()->toISOString());
        $fakeClient->method('retrieveSubscription')->willReturn($details);
        $this->app->instance(IyzicoSubscriptionClient::class, $fakeClient);

        $payload = [
            'subscriptionReferenceCode' => 'sub-ref-1',
            'eventId' => 'evt-1',
            'eventType' => 'SUBSCRIPTION_PAYMENT',
        ];
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

        $subscription->refresh();
        $this->assertSame('ACTIVE', $subscription->status);

        $tenant->refresh();
        $this->assertSame('pro', $tenant->plan_code);

        $countBefore = BillingSubscriptionEvent::query()->count();

        $this->call(
            'POST',
            route('billing.iyzico.webhook'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_IYZ_SIGNATURE_V3' => $signature],
            $body
        )->assertOk();

        $this->assertSame($countBefore, BillingSubscriptionEvent::query()->count());
    }
}
