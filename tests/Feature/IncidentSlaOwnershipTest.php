<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class IncidentSlaOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubscribedUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
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
            'features' => ['modules' => ['feature.integrations']],
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

        app(SettingsRepository::class)->set('features', 'plan_matrix', json_encode([
            strtolower($plan->slug) => ['health_dashboard', 'health_notifications', 'incidents', 'incident_sla'],
        ]));

        return $user;
    }

    private function createIncident(int $tenantId, array $overrides = []): Incident
    {
        return Incident::create(array_merge([
            'tenant_id' => $tenantId,
            'marketplace' => 'trendyol',
            'key' => 'health:'.$tenantId.':trendyol:down:'.Str::uuid(),
            'title' => 'Test incident',
            'status' => 'open',
            'severity' => 'critical',
            'first_seen_at' => now()->subMinutes(10),
            'last_seen_at' => now()->subMinutes(5),
        ], $overrides));
    }

    public function test_assign_sets_assigned_to_user_id_and_logs_event(): void
    {
        $user = $this->makeSubscribedUser();
        $incident = $this->createIncident($user->id);

        $this->actingAs($user, 'web')
            ->post(route('portal.incidents.assign', $incident), [
                'assigned_to_user_id' => $user->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'assigned_to_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('incident_events', [
            'incident_id' => $incident->id,
            'tenant_id' => $user->id,
            'type' => 'assignment',
        ]);

        $this->assertDatabaseHas('notification_audit_logs', [
            'tenant_id' => $user->id,
            'action' => 'incident_assigned',
        ]);
    }

    public function test_ack_sets_acknowledged_at_and_status_and_logs(): void
    {
        $user = $this->makeSubscribedUser();
        $incident = $this->createIncident($user->id);

        $this->actingAs($user, 'web')
            ->post(route('portal.incidents.ack', $incident))
            ->assertRedirect();

        $incident->refresh();
        $this->assertSame('acknowledged', $incident->status);
        $this->assertNotNull($incident->acknowledged_at);

        $this->assertDatabaseHas('incident_events', [
            'incident_id' => $incident->id,
            'tenant_id' => $user->id,
            'type' => 'acknowledge',
        ]);

        $this->assertDatabaseHas('notification_audit_logs', [
            'tenant_id' => $user->id,
            'action' => 'incident_acknowledged',
        ]);
    }

    public function test_sla_risk_badge_shows_when_ack_overdue(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

        $user = $this->makeSubscribedUser();
        $ackSlaMinutes = (int) config('incident_sla.ack_sla_minutes', 30);
        $this->createIncident($user->id, [
            'first_seen_at' => now()->subMinutes($ackSlaMinutes + 1),
            'last_seen_at' => now()->subMinutes(10),
            'acknowledged_at' => null,
            'resolved_at' => null,
        ]);

        $this->actingAs($user, 'web')
            ->get(route('portal.incidents.index'))
            ->assertOk()
            ->assertSee('SLA Risk');
    }

    public function test_sla_breach_badge_shows_when_resolve_overdue(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

        $user = $this->makeSubscribedUser();
        $resolveSlaMinutes = (int) config('incident_sla.resolve_sla_minutes', 240);
        $this->createIncident($user->id, [
            'first_seen_at' => now()->subMinutes($resolveSlaMinutes + 1),
            'last_seen_at' => now()->subMinutes(10),
            'acknowledged_at' => now()->subMinutes(400),
            'resolved_at' => null,
        ]);

        $this->actingAs($user, 'web')
            ->get(route('portal.incidents.index'))
            ->assertOk()
            ->assertSee('SLA Breach');
    }

    public function test_tenant_isolation_for_incident_show(): void
    {
        $userA = $this->makeSubscribedUser();
        $userB = $this->makeSubscribedUser();
        $incident = $this->createIncident($userB->id);

        app(SettingsRepository::class)->set('features', 'plan_matrix', json_encode([
            strtolower($userA->subscription->plan->slug) => ['health_dashboard', 'health_notifications', 'incidents', 'incident_sla'],
            strtolower($userB->subscription->plan->slug) => ['health_dashboard', 'health_notifications', 'incidents', 'incident_sla'],
        ]));

        $this->actingAs($userA, 'web')
            ->get(route('portal.incidents.show', $incident))
            ->assertNotFound();
    }
}

