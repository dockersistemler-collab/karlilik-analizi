<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\NotificationAuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class IncidentInboxTest extends TestCase
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
            'first_seen_at' => now()->subMinutes(30),
            'last_seen_at' => now()->subMinutes(5),
        ], $overrides));
    }

    public function test_default_inbox_shows_unassigned_open_or_ack_only(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

        $user = $this->makeSubscribedUser();

        $this->createIncident($user->id, [
            'title' => 'Unassigned Open',
            'status' => 'open',
            'assigned_to_user_id' => null,
        ]);
        $this->createIncident($user->id, [
            'title' => 'Unassigned Ack',
            'status' => 'acknowledged',
            'assigned_to_user_id' => null,
            'acknowledged_at' => now()->subMinutes(10),
        ]);
        $this->createIncident($user->id, [
            'title' => 'Assigned Open',
            'status' => 'open',
            'assigned_to_user_id' => $user->id,
        ]);
        $this->createIncident($user->id, [
            'title' => 'Resolved Incident',
            'status' => 'resolved',
            'resolved_at' => now()->subMinutes(1),
        ]);

        $this->actingAs($user, 'web')
            ->get(route('portal.incidents.inbox'))
            ->assertOk()
            ->assertSee('Unassigned Open')
            ->assertSee('Unassigned Ack')
            ->assertDontSee('Assigned Open')
            ->assertDontSee('Resolved Incident');
    }

    public function test_assign_to_me_sets_owner_and_logs_event(): void
    {
        $user = $this->makeSubscribedUser();
        $incident = $this->createIncident($user->id);

        $this->actingAs($user, 'web')
            ->post(route('portal.incidents.assign_to_me', $incident))
            ->assertRedirect();

        $incident->refresh();
        $this->assertSame($user->id, $incident->assigned_to_user_id);

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

    public function test_quick_ack_sets_acknowledged_and_logs(): void
    {
        $user = $this->makeSubscribedUser();
        $incident = $this->createIncident($user->id);

        $this->actingAs($user, 'web')
            ->post(route('portal.incidents.quick_ack', $incident))
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

    public function test_incidents_feature_disabled_redirects_to_upgrade(): void
    {
        $user = $this->makeSubscribedUser();

        app(SettingsRepository::class)->set('features', 'plan_matrix', json_encode([
            strtolower($user->subscription->plan->slug) => [],
        ]));

        $this->actingAs($user, 'web')
            ->get(route('portal.incidents.inbox'))
            ->assertRedirect(route('portal.upgrade', ['feature' => 'incidents']));
    }

    public function test_incident_sla_disabled_hides_sla_ui(): void
    {
        $user = $this->makeSubscribedUser();

        app(SettingsRepository::class)->set('features', 'plan_matrix', json_encode([
            strtolower($user->subscription->plan->slug) => ['incidents'],
        ]));

        $this->createIncident($user->id, [
            'title' => 'No SLA',
        ]);

        $this->actingAs($user, 'web')
            ->get(route('portal.incidents.inbox'))
            ->assertOk()
            ->assertDontSee('SLA Risk')
            ->assertDontSee('SLA Breach');
    }
}

