<?php

namespace Tests\Feature;

use App\Models\BillingSubscription;
use App\Models\Notification;
use App\Models\User;
use App\Services\Billing\Iyzico\Subscription\IyzicoSubscriptionClient;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SubscriptionDunningTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_sets_grace_and_keeps_plan_code(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-05 10:00:00'));

        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'plan_code' => 'pro',
        ]);

        $subscription = BillingSubscription::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'provider' => 'iyzico',
            'plan_code' => 'pro',
            'status' => 'PENDING',
            'iyzico_subscription_reference_code' => 'sub-ref-1',
        ]);

        $settings = app(SettingsRepository::class);
        $settings->set('billing', 'iyzico.webhook_secret', 'secret123', true);
        $settings->set('billing', 'dunning.grace_days', 3);

        $fakeClient = $this->createMock(IyzicoSubscriptionClient::class);
        $details = new \Iyzipay\Model\Subscription\SubscriptionDetails();
        $details->setSubscriptionStatus('UNPAID');
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
        $tenant->refresh();

        $this->assertSame('UNPAID', $subscription->status);
        $this->assertSame('pro', $tenant->plan_code);
        $this->assertSame('2026-02-05 10:00:00', $subscription->past_due_since?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-08 10:00:00', $subscription->grace_until?->format('Y-m-d H:i:s'));
    }

    public function test_active_clears_grace(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-05 10:00:00'));

        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'plan_code' => 'free',
        ]);

        $subscription = BillingSubscription::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'provider' => 'iyzico',
            'plan_code' => 'pro',
            'status' => 'UNPAID',
            'iyzico_subscription_reference_code' => 'sub-ref-2',
            'past_due_since' => now()->subDays(1),
            'grace_until' => now()->addDays(2),
            'last_dunning_sent_at' => now()->subHours(2),
        ]);

        $settings = app(SettingsRepository::class);
        $settings->set('billing', 'iyzico.webhook_secret', 'secret123', true);

        $fakeClient = $this->createMock(IyzicoSubscriptionClient::class);
        $details = new \Iyzipay\Model\Subscription\SubscriptionDetails();
        $details->setSubscriptionStatus('ACTIVE');
        $details->setPricingPlanReferenceCode('plan-1');
        $details->setStartDate(now()->toISOString());
        $fakeClient->method('retrieveSubscription')->willReturn($details);
        $this->app->instance(IyzicoSubscriptionClient::class, $fakeClient);

        $payload = [
            'subscriptionReferenceCode' => 'sub-ref-2',
            'eventId' => 'evt-2',
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
        $tenant->refresh();

        $this->assertNull($subscription->past_due_since);
        $this->assertNull($subscription->grace_until);
        $this->assertNull($subscription->last_dunning_sent_at);
        $this->assertSame('pro', $tenant->plan_code);
    }

    public function test_grace_expired_downgrades(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-05 10:00:00'));

        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'plan_code' => 'pro',
        ]);

        $subscription = BillingSubscription::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'provider' => 'iyzico',
            'plan_code' => 'pro',
            'status' => 'UNPAID',
            'past_due_since' => now()->subDays(5),
            'grace_until' => now()->subDay(),
        ]);

        $settings = app(SettingsRepository::class);
        $settings->set('billing', 'dunning.auto_downgrade', true);

        $this->artisan('billing:dunning-run')
            ->assertExitCode(0);

        $tenant->refresh();
        $subscription->refresh();

        $this->assertSame('free', $tenant->plan_code);
        $this->assertSame('SUSPENDED', $subscription->status);
    }

    public function test_reminders_are_not_duplicated(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-05 10:00:00'));

        $tenant = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'plan_code' => 'pro',
        ]);

        $subscription = BillingSubscription::create([
            'tenant_id' => $tenant->id,
            'user_id' => $tenant->id,
            'provider' => 'iyzico',
            'plan_code' => 'pro',
            'status' => 'PAST_DUE',
            'past_due_since' => now(),
            'grace_until' => now()->addDays(3),
        ]);

        $settings = app(SettingsRepository::class);
        $settings->set('billing', 'dunning.send_reminders', true);
        $settings->set('billing', 'dunning.reminder_day_1', 0);
        $settings->set('billing', 'dunning.reminder_day_2', 2);

        $this->artisan('billing:dunning-run')
            ->assertExitCode(0);

        $countAfterFirst = Notification::query()->count();
        $subscription->refresh();
        $this->assertNotNull($subscription->last_dunning_sent_at);

        $this->artisan('billing:dunning-run')
            ->assertExitCode(0);

        $this->assertSame($countAfterFirst, Notification::query()->count());
    }
}
