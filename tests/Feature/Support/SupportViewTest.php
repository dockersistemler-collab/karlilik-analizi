<?php

namespace Tests\Feature\Support;

use App\Domain\Tickets\Models\Ticket;
use App\Models\SupportAccessLog;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\SupportViewStartedNotification;
use App\Support\SupportUser;
use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Middleware\EnsureModuleEnabled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupportViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SupportUser::forgetCachedTarget();

        Route::middleware(['web', 'support.readonly'])
            ->post('/__test/support-view', fn () => response('ok'))
            ->name('test.support.post');

        Route::middleware(['web', 'support.readonly'])
            ->get('/__test/support-view-allowed', fn () => response('ok'))
            ->name('test.support.get.allowed');

        Route::middleware(['web', 'support.readonly'])
            ->get('/__test/support-view-blocked', fn () => response('ok'))
            ->name('test.support.get.blocked');

        Route::middleware(['web', 'support.readonly'])
            ->match(['OPTIONS'], '/__test/support-view-options', fn () => response('ok'))
            ->name('test.support.options');

        Route::middleware(['web', 'support.readonly'])
            ->get('/__test/admin-products', fn () => response('ok'))
            ->name('portal.products.index');

        Route::middleware(['web', 'support.readonly'])
            ->get('/__test/admin-tickets', fn () => response('ok'))
            ->name('portal.tickets.index');

        Route::middleware(['web', 'support.readonly'])
            ->get('/__test/admin-reports', fn () => response('ok'))
            ->name('portal.reports.index');

        Route::middleware(['web', 'support.readonly'])
            ->get('/__test/admin-dashboard', fn () => response('Support View aktif'))
            ->name('portal.dashboard');

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    private function createActiveSubscription(User $user): void
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-'.Str::random(8),
            'description' => 'Test',
            'price' => 0,
            'billing_period' => 'monthly',
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 0,
            'billing_period' => 'monthly',
            'auto_renew' => false,
            'usage_reset_at' => now()->addMonth(),
        ]);
    }

    public function test_super_admin_start_sets_session_and_log_without_impersonation(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);
        $this->createActiveSubscription($target);
        $this->assertTrue($target->fresh()->hasActiveSubscription());
        $this->createActiveSubscription($target);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.support-view.start', $target), [
                'reason' => 'Test reason',
            ])
            ->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticatedAs($superAdmin);
        $this->assertTrue(session('support_view_enabled'));
        $this->assertSame($superAdmin->id, session('support_view_actor_user_id'));
        $this->assertSame($target->id, session('support_view_target_user_id'));

        $log = SupportAccessLog::query()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($superAdmin->id, $log->super_admin_id);
        $this->assertSame($target->id, $log->target_user_id);
        $this->assertSame('Test reason', $log->reason);

        Notification::assertSentTo($target, SupportViewStartedNotification::class);
    }

    public function test_reason_is_required(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);
        $this->createActiveSubscription($target);
        $this->assertTrue($target->fresh()->hasActiveSubscription());
        $this->createActiveSubscription($target);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.support-view.start', $target), [
                'reason' => '',
            ])
            ->assertSessionHasErrors(['reason']);
    }

    public function test_read_only_blocks_writes_when_support_view_enabled(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);
        $this->createActiveSubscription($target);

        $log = SupportAccessLog::create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $target->id,
            'started_at' => now(),
            'reason' => 'Support',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin)
            ->withSession([
                'support_view_enabled' => true,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_target_user_id' => $target->id,
                'support_view_log_id' => $log->id,
            ])
            ->from(route('portal.dashboard'))
            ->post(route('test.support.post'))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_read_only_blocks_writes_with_json_response(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $log = SupportAccessLog::create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $target->id,
            'started_at' => now(),
            'reason' => 'Support',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin)
            ->withSession([
                'support_view_enabled' => true,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_target_user_id' => $target->id,
                'support_view_log_id' => $log->id,
            ])
            ->postJson(route('test.support.post'))
            ->assertStatus(403)
            ->assertJson([
                'error' => 'SUPPORT_VIEW_READ_ONLY',
                'message' => 'Support View modunda islem yapilamaz.',
            ]);
    }

    public function test_stop_clears_session_and_updates_log(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $log = SupportAccessLog::create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $target->id,
            'started_at' => now(),
            'reason' => 'Support',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin)
            ->withSession([
                'support_view_enabled' => true,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_target_user_id' => $target->id,
                'support_view_log_id' => $log->id,
            ])
            ->post(route('super-admin.support-view.stop'))
            ->assertRedirect(route('super-admin.users.index'));

        $this->assertFalse(session()->has('support_view_enabled'));

        $log->refresh();
        $this->assertNotNull($log->ended_at);
    }

    public function test_support_agent_start_ticket_sets_session_and_banner(): void
    {
        Notification::fake();

        $supportAgent = User::factory()->create([
            'role' => 'support_agent',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);
        $this->createActiveSubscription($target);

        $ticket = Ticket::create([
            'customer_id' => $target->id,
            'created_by_id' => $target->id,
            'assigned_to_id' => $supportAgent->id,
            'subject' => 'Support needed',
            'status' => Ticket::STATUS_OPEN,
            'priority' => Ticket::PRIORITY_LOW,
            'channel' => Ticket::CHANNEL_PANEL,
        ]);

        $this->actingAs($supportAgent)
            ->post(route('super-admin.support-view.start-ticket', $ticket), [
                'note' => 'From ticket',
            ])
            ->assertRedirect(route('portal.dashboard'));

        $this->assertTrue(session('support_view_enabled'));
        $this->assertSame($supportAgent->id, session('support_view_actor_user_id'));
        $this->assertSame($target->id, session('support_view_target_user_id'));
        $this->assertSame('ticket', session('support_view_source_type'));
        $this->assertSame($ticket->id, session('support_view_source_id'));
        $this->assertNotNull(session('support_view_expires_at'));

        $this->actingAs($supportAgent)
            ->get(route('portal.dashboard'))
            ->assertSee('Support View aktif');
    }

    public function test_support_agent_cannot_start_ticket_when_not_assigned(): void
    {
        $supportAgent = User::factory()->create([
            'role' => 'support_agent',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);
        $this->createActiveSubscription($target);
        $otherAgent = User::factory()->create([
            'role' => 'support_agent',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'customer_id' => $target->id,
            'created_by_id' => $target->id,
            'assigned_to_id' => $otherAgent->id,
            'subject' => 'Support needed',
            'status' => Ticket::STATUS_OPEN,
            'priority' => Ticket::PRIORITY_LOW,
            'channel' => Ticket::CHANNEL_PANEL,
        ]);

        $this->actingAs($supportAgent)
            ->post(route('super-admin.support-view.start-ticket', $ticket))
            ->assertSessionHasErrors(['actor']);
    }

    public function test_support_agent_cannot_start_ticket_when_ticket_closed(): void
    {
        $supportAgent = User::factory()->create([
            'role' => 'support_agent',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);
        $this->createActiveSubscription($target);

        $ticket = Ticket::create([
            'customer_id' => $target->id,
            'created_by_id' => $target->id,
            'assigned_to_id' => $supportAgent->id,
            'subject' => 'Closed ticket',
            'status' => Ticket::STATUS_CLOSED,
            'priority' => Ticket::PRIORITY_LOW,
            'channel' => Ticket::CHANNEL_PANEL,
        ]);

        $this->actingAs($supportAgent)
            ->post(route('super-admin.support-view.start-ticket', $ticket))
            ->assertSessionHasErrors(['status']);
    }

    public function test_support_view_auto_stops_when_ticket_closed(): void
    {
        $supportAgent = User::factory()->create([
            'role' => 'support_agent',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'customer_id' => $target->id,
            'created_by_id' => $target->id,
            'assigned_to_id' => $supportAgent->id,
            'subject' => 'Support needed',
            'status' => Ticket::STATUS_OPEN,
            'priority' => Ticket::PRIORITY_LOW,
            'channel' => Ticket::CHANNEL_PANEL,
        ]);

        $log = SupportAccessLog::create([
            'super_admin_id' => null,
            'actor_user_id' => $supportAgent->id,
            'actor_role' => 'support_agent',
            'source_type' => 'ticket',
            'source_id' => $ticket->id,
            'target_user_id' => $target->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'reason' => 'Ticket #'.$ticket->id,
            'scope' => 'read_only',
            'meta' => ['ticket_id' => $ticket->id],
        ]);

        $ticket->status = Ticket::STATUS_CLOSED;
        $ticket->save();

        $this->actingAs($supportAgent);
        session([
            'support_view_enabled' => true,
            'support_view_actor_user_id' => $supportAgent->id,
            'support_view_target_user_id' => $target->id,
            'support_view_log_id' => $log->id,
            'support_view_expires_at' => now()->addMinutes(60)->toIso8601String(),
            'support_view_source_type' => 'ticket',
            'support_view_source_id' => $ticket->id,
        ]);

        $this->assertFalse(SupportUser::isEnabled());
        $this->assertFalse(session()->has('support_view_enabled'));
    }

    public function test_support_view_stops_when_log_is_ended(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $log = SupportAccessLog::create([
            'super_admin_id' => $superAdmin->id,
            'actor_user_id' => $superAdmin->id,
            'actor_role' => 'super_admin',
            'source_type' => 'manual',
            'source_id' => null,
            'target_user_id' => $target->id,
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(50),
            'ended_at' => now(),
            'reason' => 'Support',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin);
        session([
            'support_view_enabled' => true,
            'support_view_actor_user_id' => $superAdmin->id,
            'support_view_target_user_id' => $target->id,
            'support_view_log_id' => $log->id,
            'support_view_expires_at' => now()->addMinutes(50)->toIso8601String(),
            'support_view_source_type' => 'manual',
            'support_view_source_id' => null,
        ]);

        $this->assertFalse(SupportUser::isEnabled());
        $this->assertFalse(session()->has('support_view_enabled'));
    }

    public function test_support_view_blocks_get_outside_allowlist(): void
    {
        config(['support.allowed_routes' => ['test.support.get.allowed']]);

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $log = SupportAccessLog::create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $target->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'reason' => 'Support',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin)
            ->withSession([
                'support_view_enabled' => true,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_target_user_id' => $target->id,
                'support_view_log_id' => $log->id,
                'support_view_expires_at' => now()->addMinutes(60)->toIso8601String(),
            ])
            ->from(route('portal.dashboard'))
            ->get(route('test.support.get.blocked'))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_support_view_allows_products_and_tickets(): void
    {
        $this->withoutMiddleware([
            EnsureActiveSubscription::class,
            EnsureModuleEnabled::class,
        ]);

        config(['support.allowed_routes' => [
            'portal.products.index',
            'portal.tickets.index',
        ]]);

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $log = SupportAccessLog::create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $target->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'reason' => 'Support',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin)
            ->withSession([
                'support_view_enabled' => true,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_target_user_id' => $target->id,
                'support_view_log_id' => $log->id,
                'support_view_expires_at' => now()->addMinutes(60)->toIso8601String(),
            ])
            ->get(route('portal.products.index'))
            ->assertOk();

        $this->actingAs($superAdmin)
            ->withSession([
                'support_view_enabled' => true,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_target_user_id' => $target->id,
                'support_view_log_id' => $log->id,
                'support_view_expires_at' => now()->addMinutes(60)->toIso8601String(),
            ])
            ->get(route('portal.tickets.index'))
            ->assertOk();
    }

    public function test_support_view_blocks_reports_routes(): void
    {
        $this->withoutMiddleware(EnsureActiveSubscription::class);

        config(['support.allowed_routes' => [
            'portal.products.index',
            'portal.tickets.index',
        ]]);

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $log = SupportAccessLog::create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $target->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'reason' => 'Support',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin)
            ->withSession([
                'support_view_enabled' => true,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_target_user_id' => $target->id,
                'support_view_log_id' => $log->id,
                'support_view_expires_at' => now()->addMinutes(60)->toIso8601String(),
            ])
            ->from(route('portal.dashboard'))
            ->get(route('portal.reports.index'))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('error');

        $this->actingAs($superAdmin)
            ->withSession([
                'support_view_enabled' => true,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_target_user_id' => $target->id,
                'support_view_log_id' => $log->id,
                'support_view_expires_at' => now()->addMinutes(60)->toIso8601String(),
            ])
            ->getJson(route('portal.reports.index'))
            ->assertStatus(403)
            ->assertJson([
                'error' => 'SUPPORT_VIEW_READ_ONLY',
                'message' => 'Bu sayfaya Support View modunda erisilemez.',
            ]);
    }

    public function test_support_view_blocks_get_outside_allowlist_with_json_response(): void
    {
        config(['support.allowed_routes' => ['test.support.get.allowed']]);

        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $log = SupportAccessLog::create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $target->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'reason' => 'Support',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin)
            ->withSession([
                'support_view_enabled' => true,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_target_user_id' => $target->id,
                'support_view_log_id' => $log->id,
                'support_view_expires_at' => now()->addMinutes(60)->toIso8601String(),
            ])
            ->getJson(route('test.support.get.blocked'))
            ->assertStatus(403)
            ->assertJson([
                'error' => 'SUPPORT_VIEW_READ_ONLY',
                'message' => 'Bu sayfaya Support View modunda erisilemez.',
            ]);
    }

    public function test_support_view_allows_options_requests(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $log = SupportAccessLog::create([
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $target->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'reason' => 'Support',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin)
            ->withSession([
                'support_view_enabled' => true,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_actor_user_id' => $superAdmin->id,
                'support_view_target_user_id' => $target->id,
                'support_view_log_id' => $log->id,
                'support_view_expires_at' => now()->addMinutes(60)->toIso8601String(),
            ])
            ->options(route('test.support.options'))
            ->assertOk();
    }
}

