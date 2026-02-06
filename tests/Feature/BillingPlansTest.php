<?php

namespace Tests\Feature;

use App\Models\BillingCheckout;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPlansTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function seedPlansCatalog(): void
    {
        app(SettingsRepository::class)->set('billing', 'plans_catalog', json_encode([
            'free' => [
                'name' => 'Free',
                'price_monthly' => 0,
                'features' => ['health_dashboard'],
                'recommended' => false,
                'contact_sales' => false,
            ],
            'pro' => [
                'name' => 'Pro',
                'price_monthly' => 999,
                'features' => ['health_dashboard', 'health_notifications', 'incidents'],
                'recommended' => true,
                'contact_sales' => false,
            ],
        ]));
    }

    private function seedFeatureMatrix(): void
    {
        app(SettingsRepository::class)->set('features', 'plan_matrix', json_encode([
            'free' => ['health_dashboard'],
            'pro' => ['health_dashboard', 'health_notifications', 'incidents', 'incident_sla'],
        ]));
    }

    private function createActiveSubscription(User $user, string $planSlug): void
    {
        $plan = Plan::create([
            'name' => ucfirst($planSlug),
            'slug' => $planSlug,
            'description' => null,
            'price' => 10,
            'yearly_price' => 100,
            'billing_period' => 'monthly',
            'max_products' => 0,
            'max_marketplaces' => 0,
            'max_orders_per_month' => 0,
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
            'amount' => 10,
            'billing_period' => 'monthly',
            'auto_renew' => true,
            'current_products_count' => 0,
            'current_marketplaces_count' => 0,
            'current_month_orders_count' => 0,
            'usage_reset_at' => now()->addMonth(),
        ]);
    }

    public function test_plans_page_renders_and_shows_current_plan(): void
    {
        $user = $this->makeUser(['plan_code' => 'free']);
        $this->seedPlansCatalog();

        $this->actingAs($user)
            ->get(route('portal.billing.plans'))
            ->assertOk()
            ->assertSee('Mevcut Plan')
            ->assertSee('Free');
    }

    public function test_checkout_creates_pending_record(): void
    {
        $user = $this->makeUser();
        $this->seedPlansCatalog();
        app(SettingsRepository::class)->set('billing', 'iyzico.enabled', false);

        $this->actingAs($user)
            ->post(route('portal.billing.checkout'), ['plan_code' => 'pro'])
            ->assertRedirect();

        $this->assertDatabaseHas('billing_checkouts', [
            'tenant_id' => $user->id,
            'plan_code' => 'pro',
            'status' => 'pending',
        ]);
    }

    public function test_success_marks_checkout_and_updates_plan_code(): void
    {
        $user = $this->makeUser();
        $this->seedPlansCatalog();
        app(SettingsRepository::class)->set('billing', 'iyzico.enabled', false);

        $this->actingAs($user)
            ->post(route('portal.billing.checkout'), ['plan_code' => 'pro']);

        $checkout = BillingCheckout::query()->first();

        $this->actingAs($user)
            ->get(route('portal.billing.success', ['checkout' => $checkout->id]))
            ->assertOk();

        $this->assertDatabaseHas('billing_checkouts', [
            'id' => $checkout->id,
            'status' => 'completed',
        ]);

        $user->refresh();
        $this->assertSame('pro', $user->plan_code);
        $this->assertNotNull($user->plan_started_at);
    }

    public function test_upgrade_enables_incident_routes_when_plan_allows(): void
    {
        $user = $this->makeUser();
        $this->seedPlansCatalog();
        $this->seedFeatureMatrix();
        app(SettingsRepository::class)->set('billing', 'iyzico.enabled', false);
        $this->createActiveSubscription($user, 'free');

        $this->actingAs($user)
            ->post(route('portal.billing.checkout'), ['plan_code' => 'pro']);

        $checkout = BillingCheckout::query()->first();

        $this->actingAs($user)
            ->get(route('portal.billing.success', ['checkout' => $checkout->id]));

        $this->actingAs($user)
            ->get(route('portal.incidents.inbox'))
            ->assertOk();
    }
}

