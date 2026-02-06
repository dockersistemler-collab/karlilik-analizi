<?php

namespace Tests\Feature\Support;

use App\Models\SupportAccessLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportViewSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_sessions_page(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.support-view-sessions.index'))
            ->assertOk();
    }

    public function test_client_cannot_access_sessions_page(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $this->actingAs($client)
            ->get(route('super-admin.support-view-sessions.index'))
            ->assertForbidden();
    }

    public function test_active_log_is_listed(): void
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
            'started_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'reason' => 'Support session',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.support-view-sessions.index'))
            ->assertOk()
            ->assertSee($log->reason);
    }

    public function test_end_route_sets_ended_at(): void
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
            'started_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'reason' => 'Support session',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.support-view-sessions.end', $log))
            ->assertRedirect();

        $log->refresh();
        $this->assertNotNull($log->ended_at);
        $this->assertIsArray($log->meta);
        $this->assertSame($superAdmin->id, $log->meta['ended_by_user_id'] ?? null);
        $this->assertSame('super_admin', $log->meta['ended_by_role'] ?? null);
    }

    public function test_ended_log_is_not_listed_in_active_filter(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $endedLog = SupportAccessLog::create([
            'super_admin_id' => $superAdmin->id,
            'actor_user_id' => $superAdmin->id,
            'actor_role' => 'super_admin',
            'source_type' => 'manual',
            'source_id' => null,
            'target_user_id' => $target->id,
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(30),
            'ended_at' => now(),
            'reason' => 'Ended session',
            'scope' => 'read_only',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.support-view-sessions.index'))
            ->assertOk()
            ->assertDontSee($endedLog->reason);
    }
}
