<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Billing\Iyzico\Subscription\IyzicoSubscriptionClient;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminIyzicoCatalogAutoCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_create_updates_catalog(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $settings = app(SettingsRepository::class);
        $settings->set('billing', 'iyzico.enabled', true, false, $superAdmin->id);
        $settings->set('billing', 'iyzico.api_key', 'key', true, $superAdmin->id);
        $settings->set('billing', 'iyzico.secret_key', 'secret', true, $superAdmin->id);
        $settings->set('billing', 'plans_catalog', json_encode([
            'pro' => [
                'name' => 'Pro',
                'price_monthly' => 999,
                'features' => [],
                'recommended' => false,
                'contact_sales' => false,
                'iyzico' => [
                    'productReferenceCode' => '',
                    'pricingPlanReferenceCode' => '',
                ],
            ],
        ]), false, $superAdmin->id);

        $this->mock(IyzicoSubscriptionClient::class, function ($mock) {
            $mock->shouldReceive('createProduct')
                ->once()
                ->andReturn('PRC123');
        });

        $this->actingAs($superAdmin)
            ->postJson(route('super-admin.system-settings.billing.iyzico.product-create'), [
                'plan_code' => 'pro',
            ])
            ->assertOk()
            ->assertJsonFragment(['productReferenceCode' => 'PRC123']);

        $catalog = json_decode((string) $settings->get('billing', 'plans_catalog'), true);
        $this->assertSame('PRC123', $catalog['pro']['iyzico']['productReferenceCode']);
    }

    public function test_pricing_plan_create_updates_catalog(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $settings = app(SettingsRepository::class);
        $settings->set('billing', 'iyzico.enabled', true, false, $superAdmin->id);
        $settings->set('billing', 'iyzico.api_key', 'key', true, $superAdmin->id);
        $settings->set('billing', 'iyzico.secret_key', 'secret', true, $superAdmin->id);
        $settings->set('billing', 'plans_catalog', json_encode([
            'pro' => [
                'name' => 'Pro',
                'price_monthly' => 999,
                'features' => [],
                'recommended' => false,
                'contact_sales' => false,
                'iyzico' => [
                    'productReferenceCode' => 'PRC123',
                    'pricingPlanReferenceCode' => '',
                ],
            ],
        ]), false, $superAdmin->id);

        $this->mock(IyzicoSubscriptionClient::class, function ($mock) {
            $mock->shouldReceive('createPricingPlan')
                ->once()
                ->andReturn('PPC456');
        });

        $this->actingAs($superAdmin)
            ->postJson(route('super-admin.system-settings.billing.iyzico.pricing-plan-create'), [
                'plan_code' => 'pro',
            ])
            ->assertOk()
            ->assertJsonFragment(['pricingPlanReferenceCode' => 'PPC456']);

        $catalog = json_decode((string) $settings->get('billing', 'plans_catalog'), true);
        $this->assertSame('PPC456', $catalog['pro']['iyzico']['pricingPlanReferenceCode']);
    }

    public function test_pricing_plan_requires_product_reference(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $settings = app(SettingsRepository::class);
        $settings->set('billing', 'iyzico.enabled', true, false, $superAdmin->id);
        $settings->set('billing', 'iyzico.api_key', 'key', true, $superAdmin->id);
        $settings->set('billing', 'iyzico.secret_key', 'secret', true, $superAdmin->id);
        $settings->set('billing', 'plans_catalog', json_encode([
            'pro' => [
                'name' => 'Pro',
                'price_monthly' => 999,
                'features' => [],
                'recommended' => false,
                'contact_sales' => false,
                'iyzico' => [
                    'productReferenceCode' => '',
                    'pricingPlanReferenceCode' => '',
                ],
            ],
        ]), false, $superAdmin->id);

        $this->mock(IyzicoSubscriptionClient::class, function ($mock) {
            $mock->shouldNotReceive('createPricingPlan');
        });

        $this->actingAs($superAdmin)
            ->postJson(route('super-admin.system-settings.billing.iyzico.pricing-plan-create'), [
                'plan_code' => 'pro',
            ])
            ->assertStatus(422);
    }
}
