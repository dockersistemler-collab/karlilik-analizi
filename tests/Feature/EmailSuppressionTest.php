<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationEmailJob;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Notifications\EmailSuppressionService;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class EmailSuppressionTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_tenant_suppression_blocks_only_that_tenant(): void
    {
        Queue::fake();

        $userA = $this->makeSubscribedUser([
            'email' => 'tenant-a@example.com',
            'notification_email' => 'shared@example.com',
        ]);
        $userB = $this->makeSubscribedUser([
            'email' => 'tenant-b@example.com',
            'notification_email' => 'shared@example.com',
        ]);

        app(EmailSuppressionService::class)->suppress($userA->id, 'shared@example.com', 'manual', 'admin');

        app(NotificationService::class)->notifyUser($userA, [
            'tenant_id' => $userA->id,
            'user_id' => $userA->id,
            'type' => 'critical',
            'title' => 'Tenant A Alert',
            'body' => 'Body',
        ]);

        app(NotificationService::class)->notifyUser($userB, [
            'tenant_id' => $userB->id,
            'user_id' => $userB->id,
            'type' => 'critical',
            'title' => 'Tenant B Alert',
            'body' => 'Body',
        ]);

        Queue::assertPushed(SendNotificationEmailJob::class, function (SendNotificationEmailJob $job) use ($userB): bool {
            $notification = Notification::query()->find($job->notificationId);
            return (int) $notification?->tenant_id === (int) $userB->id;
        });

        Queue::assertNotPushed(SendNotificationEmailJob::class, function (SendNotificationEmailJob $job) use ($userA): bool {
            $notification = Notification::query()->find($job->notificationId);
            return (int) $notification?->tenant_id === (int) $userA->id;
        });
    }

    public function test_global_suppression_blocks_all_tenants(): void
    {
        Queue::fake();

        $userA = $this->makeSubscribedUser([
            'email' => 'tenant-a2@example.com',
            'notification_email' => 'global@example.com',
        ]);
        $userB = $this->makeSubscribedUser([
            'email' => 'tenant-b2@example.com',
            'notification_email' => 'global@example.com',
        ]);

        app(EmailSuppressionService::class)->suppress(null, 'global@example.com', 'manual', 'admin');

        app(NotificationService::class)->notifyUser($userA, [
            'tenant_id' => $userA->id,
            'user_id' => $userA->id,
            'type' => 'critical',
            'title' => 'Tenant A Alert',
            'body' => 'Body',
        ]);

        app(NotificationService::class)->notifyUser($userB, [
            'tenant_id' => $userB->id,
            'user_id' => $userB->id,
            'type' => 'critical',
            'title' => 'Tenant B Alert',
            'body' => 'Body',
        ]);

        Queue::assertNotPushed(SendNotificationEmailJob::class);
    }

    public function test_hard_fail_exception_creates_suppression(): void
    {
        $user = $this->makeSubscribedUser(['email' => 'fail@example.com']);

        $notification = Notification::create([
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'channel' => 'email',
            'type' => 'critical',
            'title' => 'Fail Alert',
            'body' => 'Body',
        ]);

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('550 user unknown'));

        $job = new SendNotificationEmailJob($notification->id);
        $job->handle(app(NotificationService::class), app(EmailSuppressionService::class));

        $this->assertDatabaseHas('email_suppressions', [
            'tenant_id' => $user->id,
            'email' => 'fail@example.com',
            'reason' => 'hard_fail',
            'source' => 'smtp',
        ]);

        $this->assertDatabaseHas('notification_audit_logs', [
            'tenant_id' => $user->id,
            'action' => 'email_failed',
        ]);

        $this->assertDatabaseHas('notification_audit_logs', [
            'tenant_id' => $user->id,
            'action' => 'email_suppressed',
        ]);
    }

    public function test_config_suppressed_email_is_blocked_even_without_db(): void
    {
        Queue::fake();

        config(['mail.suppressed' => ['blocked@example.com']]);

        $user = $this->makeSubscribedUser(['email' => 'blocked@example.com']);

        app(NotificationService::class)->notifyUser($user, [
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'critical',
            'title' => 'Blocked Alert',
            'body' => 'Body',
        ]);

        Queue::assertNotPushed(SendNotificationEmailJob::class);
    }
}
