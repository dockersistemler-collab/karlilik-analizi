<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminIntegrationHealthSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_integration_health_settings(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.settings.index', ['tab' => 'health']))
            ->assertOk()
            ->assertSee('Integration Health Ayarları', false);
    }

    public function test_normal_admin_cannot_access_integration_health_settings(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('super-admin.settings.index', ['tab' => 'health']))
            ->assertForbidden();
    }

    public function test_integration_health_settings_saved_and_override_config(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.settings.health.update'), [
                'stale_minutes' => 45,
                'window_hours' => 12,
                'degraded_error_threshold' => 5,
                'down_requires_critical' => '0',
            ])
            ->assertRedirect(route('super-admin.settings.index', ['tab' => 'health']));

        $settings = app(SettingsRepository::class);
        $this->assertSame('45', $settings->get('integration_health', 'stale_minutes'));
        $this->assertSame('12', $settings->get('integration_health', 'window_hours'));
        $this->assertSame('5', $settings->get('integration_health', 'degraded_error_threshold'));
        $this->assertSame('false', $settings->get('integration_health', 'down_requires_critical'));

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $this->assertSame(45, config('integration_health.stale_minutes'));
        $this->assertSame(12, config('integration_health.window_hours'));
        $this->assertSame(5, config('integration_health.degraded_error_threshold'));
        $this->assertFalse(config('integration_health.down_requires_critical'));
    }
}
