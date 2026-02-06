<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminBillingPlansSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_save_billing_plans_catalog(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $payload = [
            'plans' => [
                'free' => [
                    'name' => 'Free',
                    'price_monthly' => 0,
                    'recommended' => false,
                    'contact_sales' => false,
                    'features' => ['health_dashboard'],
                ],
                'pro' => [
                    'name' => 'Pro',
                    'price_monthly' => 999,
                    'recommended' => true,
                    'contact_sales' => false,
                    'features' => ['health_dashboard', 'health_notifications'],
                ],
            ],
        ];

        $this->actingAs($superAdmin)
            ->post(route('super-admin.settings.billing.update'), $payload)
            ->assertRedirect(route('super-admin.settings.index', ['tab' => 'billing']));

        $settings = app(SettingsRepository::class);
        $catalog = json_decode((string) $settings->get('billing', 'plans_catalog'), true);

        $this->assertIsArray($catalog);
        $this->assertSame('Free', $catalog['free']['name']);
        $this->assertSame(999, $catalog['pro']['price_monthly']);
    }
}
