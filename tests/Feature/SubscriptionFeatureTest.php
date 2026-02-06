<?php

namespace Tests\Feature;

use App\Models\BillingSubscription;
use App\Models\User;
use App\Services\Billing\Iyzico\Subscription\IyzicoSubscriptionClient;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_requires_mapping(): void
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
                'iyzico' => [
                    'productReferenceCode' => '',
                    'pricingPlanReferenceCode' => '',
                ],
            ],
        ]));

        $this->actingAs($user)
            ->post(route('portal.billing.subscribe'), ['plan_code' => 'pro'])
            ->assertRedirect(route('portal.billing.plans'))
            ->assertSessionHas('error');
    }

    public function test_subscribe_creates_subscription_record(): void
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
                'iyzico' => [
                    'productReferenceCode' => 'prod-1',
                    'pricingPlanReferenceCode' => 'plan-1',
                ],
            ],
        ]));

        $fakeClient = $this->createMock(IyzicoSubscriptionClient::class);
        $fakeResponse = $this->createMock(\Iyzipay\Model\Subscription\SubscriptionCreateCheckoutForm::class);
        $fakeResponse->method('getToken')->willReturn('tok-sub-1');
        $fakeResponse->method('getCheckoutFormContent')->willReturn('<iframe>sub</iframe>');
        $fakeClient->method('createSubscriptionCheckoutForm')->willReturn($fakeResponse);

        $this->app->instance(IyzicoSubscriptionClient::class, $fakeClient);

        $response = $this->actingAs($user)
            ->post(route('portal.billing.subscribe'), ['plan_code' => 'pro']);

        $subscription = BillingSubscription::query()->first();

        $this->assertNotNull($subscription);
        $response->assertRedirect(route('portal.billing.subscription.show', $subscription->id));

        $this->assertDatabaseHas('billing_subscriptions', [
            'tenant_id' => $user->id,
            'plan_code' => 'pro',
            'iyzico_checkout_form_token' => 'tok-sub-1',
        ]);
    }
}

