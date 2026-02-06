<?php

namespace Tests\Feature\Notifications;

use App\Jobs\SendNotificationEmailJob;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use App\Http\Middleware\EnsureSupportViewReadOnly;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationHubTest extends TestCase
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

    public function test_tenant_isolation_hides_other_users_notifications(): void
    {
        $userA = $this->makeSubscribedUser();
        $userB = $this->makeSubscribedUser();

        Notification::factory()->create([
            'tenant_id' => $userA->id,
            'user_id' => $userA->id,
            'title' => 'Tenant A Notification',
        ]);

        Notification::factory()->create([
            'tenant_id' => $userB->id,
            'user_id' => $userB->id,
            'title' => 'Tenant B Notification',
        ]);

        $this->actingAs($userA)
            ->get(route('portal.notification-hub.notifications.index'))
            ->assertOk()
            ->assertSee('Tenant A Notification')
            ->assertDontSee('Tenant B Notification');
    }

    public function test_preference_disable_blocks_email_dispatch(): void
    {
        Queue::fake();

        $user = $this->makeSubscribedUser();

        NotificationPreference::create([
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'critical',
            'channel' => 'email',
            'marketplace' => null,
            'enabled' => false,
        ]);

        app(NotificationService::class)->notifyUser($user, [
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'critical',
            'title' => 'Test Notification',
            'body' => 'Test body',
        ]);

        Queue::assertNotPushed(SendNotificationEmailJob::class);

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $user->id,
            'channel' => 'in_app',
            'title' => 'Test Notification',
        ]);

        $this->assertDatabaseMissing('app_notifications', [
            'tenant_id' => $user->id,
            'channel' => 'email',
            'title' => 'Test Notification',
        ]);
    }

    public function test_dedupe_key_prevents_duplicate_records_within_window(): void
    {
        $user = $this->makeSubscribedUser();
        $service = app(NotificationService::class);

        $payload = [
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'type' => 'operational',
            'channel' => 'in_app',
            'dedupe_key' => 'order_sync_failed:123',
            'title' => 'Order Sync Failed',
            'body' => 'Order sync failed',
        ];

        $first = $service->createNotification($payload);
        $second = $service->createNotification($payload);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('app_notifications', 1);
    }

    public function test_support_view_requires_reason_for_preferences_update(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $client = $this->makeSubscribedUser();

        $this->actingAs($superAdmin)
            ->withSession([
                'support_view_enabled' => true,
                'support_view_target_user_id' => $client->id,
                'support_view_expires_at' => now()->addHour()->toDateTimeString(),
            ])
            ->withoutMiddleware(EnsureSupportViewReadOnly::class)
            ->put(route('portal.notification-hub.preferences.update'), [
                'marketplace' => null,
                'preferences' => [
                    'critical' => [
                        'in_app' => 1,
                        'email' => 1,
                    ],
                ],
            ])
            ->assertSessionHasErrors('support_reason');
    }
}

