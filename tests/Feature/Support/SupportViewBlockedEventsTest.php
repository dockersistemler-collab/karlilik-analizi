<?php

namespace Tests\Feature\Support;

use App\Models\SupportAccessEvent;
use App\Models\SupportAccessLog;
use App\Models\User;
use App\Support\SupportUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SupportViewBlockedEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SupportUser::forgetCachedTarget();

        Route::middleware(['web', 'support.readonly'])
            ->get('/__test/support-view-blocked-get', fn () => response('ok'))
            ->name('test.support.blocked.get');

        Route::middleware(['web', 'support.readonly'])
            ->post('/__test/support-view-blocked-post', fn () => response('ok'))
            ->name('test.support.blocked.post');

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    public function test_blocked_get_creates_event_with_query_keys(): void
    {
        config(['support.allowed_routes' => []]);

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
                'support_view_source_type' => 'manual',
                'support_view_source_id' => null,
            ])
            ->getJson(route('test.support.blocked.get', ['page' => 2]))
            ->assertStatus(403);

        $event = SupportAccessEvent::query()->first();
        $this->assertNotNull($event);
        $this->assertSame('BLOCKED_GET', $event->type);
        $this->assertSame('GET', $event->method);
        $this->assertSame('test.support.blocked.get', $event->route_name);
        $this->assertSame($superAdmin->id, $event->actor_user_id);
        $this->assertSame($target->id, $event->target_user_id);
        $this->assertIsArray($event->payload);
        $this->assertEqualsCanonicalizing(['page'], $event->payload['query_keys'] ?? []);
        $this->assertContains('page', $event->payload['input_keys'] ?? []);
    }

    public function test_blocked_write_creates_event_with_input_keys_only(): void
    {
        config(['support.allowed_routes' => []]);

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
                'support_view_source_type' => 'manual',
                'support_view_source_id' => null,
            ])
            ->postJson(route('test.support.blocked.post'), [
                'name' => 'Demo',
                'password' => 'secret',
                'token' => 'abc',
            ])
            ->assertStatus(403);

        $event = SupportAccessEvent::query()->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame('BLOCKED_WRITE', $event->type);
        $this->assertSame('POST', $event->method);
        $this->assertSame('test.support.blocked.post', $event->route_name);
        $this->assertIsArray($event->payload);
        $this->assertContains('name', $event->payload['input_keys'] ?? []);
        $this->assertNotContains('password', $event->payload['input_keys'] ?? []);
        $this->assertNotContains('token', $event->payload['input_keys'] ?? []);
    }
}
