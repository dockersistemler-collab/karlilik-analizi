<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminIncidentSlaSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_incident_sla_settings(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.settings.index', ['tab' => 'incident-sla']))
            ->assertOk()
            ->assertSee('Incident & SLA Ayarları', false);
    }

    public function test_normal_admin_cannot_access_incident_sla_settings(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('super-admin.settings.index', ['tab' => 'incident-sla']))
            ->assertForbidden();
    }

    public function test_incident_sla_settings_saved_and_override_config(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.settings.incident-sla.update'), [
                'ack_sla_minutes' => 45,
                'resolve_sla_minutes' => 300,
            ])
            ->assertRedirect(route('super-admin.settings.index', ['tab' => 'incident-sla']));

        $settings = app(SettingsRepository::class);
        $this->assertSame('45', $settings->get('incident_sla', 'ack_sla_minutes'));
        $this->assertSame('300', $settings->get('incident_sla', 'resolve_sla_minutes'));

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $this->assertSame(45, config('incident_sla.ack_sla_minutes'));
        $this->assertSame(300, config('incident_sla.resolve_sla_minutes'));
    }
}
