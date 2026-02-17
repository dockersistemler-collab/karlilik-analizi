<?php

namespace Tests\Feature;

use App\Models\Marketplace;
use App\Models\MarketplaceCredential;
use App\Models\MarketplaceProduct;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegrationHealthTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubscribedUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ], $overrides));

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

        app(SettingsRepository::class)->set('features', 'plan_matrix', json_encode([
            strtolower($plan->slug) => ['health_dashboard'],
        ]));

        return $user;
    }

    private function seedMarketplace(string $code, string $name = 'Trendyol'): Marketplace
    {
        return Marketplace::create([
            'name' => $name,
            'code' => $code,
            'api_url' => null,
            'is_active' => true,
        ]);
    }

    private function makeProduct(int $userId, string $sku): Product
    {
        return Product::create([
            'user_id' => $userId,
            'sku' => $sku,
            'name' => 'Test urun '.$sku,
            'price' => 100,
            'currency' => 'TRY',
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
    }

    public function test_health_ok_when_recent_success_and_no_critical(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

        $user = $this->makeSubscribedUser();
        $marketplace = $this->seedMarketplace('trendyol');
        MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
        ]);

        $product = $this->makeProduct($user->id, 'SKU-OK-'.Str::uuid());
        MarketplaceProduct::create([
            'product_id' => $product->id,
            'marketplace_id' => $marketplace->id,
            'price' => 120,
            'stock_quantity' => 5,
            'last_sync_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($user, 'web')
            ->get(route('portal.integrations.health'))
            ->assertOk()
            ->assertViewHas('healthSummary', function (array $summary): bool {
                return $summary[0]['status'] === 'OK';
            });
    }

    public function test_health_degraded_when_errors_exist_but_success_recent(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

        $user = $this->makeSubscribedUser();
        $marketplace = $this->seedMarketplace('n11', 'N11');
        MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
        ]);

        $product = $this->makeProduct($user->id, 'SKU-DEG-'.Str::uuid());
        MarketplaceProduct::create([
            'product_id' => $product->id,
            'marketplace_id' => $marketplace->id,
            'price' => 150,
            'stock_quantity' => 8,
            'last_sync_at' => now()->subMinutes(5),
        ]);

        Notification::factory()->create([
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'marketplace' => 'n11',
            'source' => 'order_sync',
            'type' => 'operational',
            'title' => 'Order sync failed',
            'body' => 'fail',
            'created_at' => now()->subHours(2),
        ]);

        $this->actingAs($user, 'web')
            ->get(route('portal.integrations.health'))
            ->assertOk()
            ->assertViewHas('healthSummary', function (array $summary): bool {
                return $summary[0]['status'] === 'DEGRADED';
            });
    }

    public function test_health_down_when_recent_critical_and_no_recent_success(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

        $user = $this->makeSubscribedUser();
        $marketplace = $this->seedMarketplace('hb', 'Hepsiburada');
        MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
        ]);

        Notification::factory()->create([
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'marketplace' => 'hb',
            'source' => 'invoice',
            'type' => 'critical',
            'title' => 'Invoice failed',
            'body' => 'fail',
            'created_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($user, 'web')
            ->get(route('portal.integrations.health'))
            ->assertOk()
            ->assertViewHas('healthSummary', function (array $summary): bool {
                return $summary[0]['status'] === 'DOWN';
            });
    }

    public function test_tenant_isolation_for_health_notifications(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

        $userA = $this->makeSubscribedUser();
        $userB = $this->makeSubscribedUser();
        $marketplace = $this->seedMarketplace('ty', 'Trendyol');

        app(SettingsRepository::class)->set('features', 'plan_matrix', json_encode([
            strtolower($userA->subscription->plan->slug) => ['health_dashboard'],
            strtolower($userB->subscription->plan->slug) => ['health_dashboard'],
        ]));

        MarketplaceCredential::create([
            'user_id' => $userA->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
        ]);

        MarketplaceCredential::create([
            'user_id' => $userB->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
        ]);

        Notification::factory()->create([
            'tenant_id' => $userB->id,
            'user_id' => $userB->id,
            'marketplace' => 'ty',
            'source' => 'order_sync',
            'type' => 'critical',
            'title' => 'B error',
            'body' => 'fail',
            'created_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($userA, 'web')
            ->get(route('portal.integrations.health'))
            ->assertOk()
            ->assertViewHas('healthSummary', function (array $summary): bool {
                return $summary[0]['status'] === 'OK';
            });
    }
}

