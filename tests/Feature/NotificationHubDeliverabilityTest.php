<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationEmailJob;
use App\Models\NotificationPreference;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationHubDeliverabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeSubscribedUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => 'client',
            'is_active' => true,
        ], $overrides));

        $plan = Plan::create([
            'name' => 'Test',
            'slug' => 'test-plan-'.$user->id,
            'description' => null,
            'price' => 1,
            'yearly_price' => 10,
            'billing_period' => 'monthly',
            'max_products' => 0,
            'max_marketplaces' => 0,
            'max_orders_per_month' => 0,
            'max_tickets_per_month' => 0,
            'api_access' => false,
            'advanced_reports' => false,
            'priority_support' => false,
            'custom_integrations' => false,
            'features' => ['modules' => []],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'cancelled_at' => null,
            'amount' => 1,
            'billing_period' => 'monthly',
            'auto_renew' => true,
            'current_products_count' => 0,
            'current_marketplaces_count' => 0,
            'current_month_orders_count' => 0,
            'usage_reset_at' => now()->addMonth(),
        ]);

        return $user;
    }

    public function test_critical_defaults_to_email_and_in_app_when_no_preference_exists(): void
    {
        Queue::fake();

        $user = $this->makeSubscribedUser();

        app(NotificationService::class)->notifyUser($user, [
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'critical',
            'title' => 'Critical Alert',
            'body' => 'Critical body',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $user->id,
            'channel' => 'in_app',
            'title' => 'Critical Alert',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $user->id,
            'channel' => 'email',
            'title' => 'Critical Alert',
        ]);

        Queue::assertPushed(SendNotificationEmailJob::class);
    }

    public function test_operational_does_not_send_email_by_default(): void
    {
        Queue::fake();

        $user = $this->makeSubscribedUser();

        app(NotificationService::class)->notifyUser($user, [
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'operational',
            'title' => 'Operational Alert',
            'body' => 'Operational body',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $user->id,
            'channel' => 'in_app',
            'title' => 'Operational Alert',
        ]);

        $this->assertDatabaseMissing('app_notifications', [
            'tenant_id' => $user->id,
            'channel' => 'email',
            'title' => 'Operational Alert',
        ]);

        Queue::assertNotPushed(SendNotificationEmailJob::class);
    }

    public function test_quiet_hours_defers_email_dispatch(): void
    {
        Queue::fake();

        $user = $this->makeSubscribedUser();

        NotificationPreference::create([
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'critical',
            'channel' => 'email',
            'marketplace' => null,
            'enabled' => true,
            'quiet_hours' => [
                'start' => '22:00',
                'end' => '08:00',
                'tz' => 'Europe/Istanbul',
            ],
        ]);

        Carbon::setTestNow(Carbon::create(2026, 2, 5, 23, 30, 0, 'Europe/Istanbul'));

        $service = app(NotificationService::class);
        $expectedRelease = $service->nextQuietHoursReleaseAt([
            'start' => '22:00',
            'end' => '08:00',
            'tz' => 'Europe/Istanbul',
        ], Carbon::now());

        $service->notifyUser($user, [
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'critical',
            'title' => 'Quiet Hours Alert',
            'body' => 'Quiet hours body',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $user->id,
            'channel' => 'in_app',
            'title' => 'Quiet Hours Alert',
        ]);

        Queue::assertPushed(SendNotificationEmailJob::class, function (SendNotificationEmailJob $job) use ($expectedRelease): bool {
            if (!$expectedRelease) {
                return false;
            }

            if (!$job->delay instanceof \DateTimeInterface) {
                return false;
            }

            $delayAt = Carbon::instance($job->delay);
            return $delayAt->diffInSeconds($expectedRelease) < 5;
        });
    }

    public function test_email_retry_policy_is_configured_for_email_job(): void
    {
        $job = new SendNotificationEmailJob('test-id');

        $this->assertTrue(property_exists($job, 'tries'));
        $this->assertGreaterThanOrEqual(3, $job->tries);

        $this->assertTrue(method_exists($job, 'backoff'));
        $backoff = $job->backoff();

        if (is_array($backoff)) {
            $this->assertNotEmpty($backoff);
            foreach ($backoff as $delay) {
                $this->assertIsInt($delay);
                $this->assertGreaterThan(0, $delay);
            }
        } else {
            $this->assertIsInt($backoff);
            $this->assertGreaterThan(0, $backoff);
        }
    }

    public function test_suppression_list_blocks_email_dispatch(): void
    {
        Queue::fake();

        config(['mail.suppressed' => ['blocked@example.com']]);

        $user = $this->makeSubscribedUser([
            'notification_email' => 'blocked@example.com',
        ]);

        app(NotificationService::class)->notifyUser($user, [
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'critical',
            'title' => 'Suppressed Alert',
            'body' => 'Suppressed body',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $user->id,
            'channel' => 'in_app',
            'title' => 'Suppressed Alert',
        ]);

        Queue::assertNotPushed(SendNotificationEmailJob::class);
    }
}
