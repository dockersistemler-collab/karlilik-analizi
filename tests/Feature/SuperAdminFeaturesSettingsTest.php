<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminFeaturesSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_save_feature_matrix(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.settings.features.update'), [
                'plan_code' => 'pro',
                'features' => ['health_dashboard', 'health_notifications'],
            ])
            ->assertRedirect(route('super-admin.settings.index', ['tab' => 'features']));

        $settings = app(SettingsRepository::class);
        $matrix = json_decode((string) $settings->get('features', 'plan_matrix'), true);

        $this->assertIsArray($matrix);
        $this->assertSame(['health_dashboard', 'health_notifications'], $matrix['pro']);
    }
}
