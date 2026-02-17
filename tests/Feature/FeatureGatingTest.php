<?php

namespace Tests\Feature;

use App\Models\Marketplace;
use App\Models\MarketplaceCredential;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\IntegrationHealthNotifier;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FeatureGatingTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubscribedUser(): User
    {
        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Module::query()->firstOrCreate(
            ['code' => 'feature.integrations'],
            [
                'name' => 'Integrations',
                'description' => null,
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

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

        return $user;
    }

    public function test_health_dashboard_disabled_redirects_to_upgrade(): void
    {
        $user = $this->makeSubscribedUser();
        $planCode = strtolower($user->subscription->plan->slug);

        $user->plan_code = 'custom-plan';
        $user->save();

        app(SettingsRepository::class)->set('features', 'plan_matrix', json_encode([
            'custom-plan' => [],
            $planCode => ['health_dashboard'],
        ]));

        $this->actingAs($user, 'web')
            ->get(route('portal.integrations.health'))
            ->assertRedirect(route('portal.upgrade', ['feature' => 'health_dashboard']));
    }

    public function test_incidents_disabled_redirects_to_upgrade(): void
    {
        $user = $this->makeSubscribedUser();
        $planCode = strtolower($user->subscription->plan->slug);

        app(SettingsRepository::class)->set('features', 'plan_matrix', json_encode([
            $planCode => ['health_dashboard'],
        ]));

        $this->actingAs($user, 'web')
            ->get(route('portal.incidents.index'))
            ->assertRedirect(route('portal.upgrade', ['feature' => 'incidents']));
    }

    public function test_health_notifications_disabled_blocks_notifier(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

        $user = $this->makeSubscribedUser();
        $planCode = strtolower($user->subscription->plan->slug);

        app(SettingsRepository::class)->set('features', 'plan_matrix', json_encode([
            $planCode => ['health_dashboard', 'incidents'],
        ]));

        $marketplace = Marketplace::create([
            'name' => 'Trendyol',
            'code' => 'ty',
            'api_url' => null,
            'is_active' => true,
        ]);

        MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
        ]);

        Notification::factory()->create([
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'marketplace' => 'ty',
            'source' => 'invoice',
            'type' => 'critical',
            'title' => 'Invoice failed',
            'body' => 'fail',
            'created_at' => now()->subMinutes(10),
        ]);

        app(IntegrationHealthNotifier::class)->notifyTenant($user->id);

        $this->assertSame(0, \App\Models\Notification::query()
            ->where('tenant_id', $user->id)
            ->where('marketplace', 'ty')
            ->where('dedupe_key', 'health:'.$user->id.':ty:down')
            ->count());
    }

    public function test_tenant_isolation_for_plan_matrix(): void
    {
        $userA = $this->makeSubscribedUser();
        $userB = $this->makeSubscribedUser();

        $planA = strtolower($userA->subscription->plan->slug);
        $planB = strtolower($userB->subscription->plan->slug);

        app(SettingsRepository::class)->set('features', 'plan_matrix', json_encode([
            $planA => ['health_dashboard'],
            $planB => [],
        ]));

        $this->actingAs($userA, 'web')
            ->get(route('portal.integrations.health'))
            ->assertOk();

        $this->actingAs($userB, 'web')
            ->get(route('portal.integrations.health'))
            ->assertRedirect(route('portal.upgrade', ['feature' => 'health_dashboard']));
    }
}

