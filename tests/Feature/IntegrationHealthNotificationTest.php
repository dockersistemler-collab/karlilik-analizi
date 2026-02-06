<?php

namespace Tests\Feature;

use App\Models\Marketplace;
use App\Models\MarketplaceCredential;
use App\Models\MarketplaceProduct;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Services\IntegrationHealthNotifier;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegrationHealthNotificationTest extends TestCase
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

    public function test_down_creates_critical_notification(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

        $user = $this->makeSubscribedUser();
        $marketplace = $this->seedMarketplace('trendyol');
        MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
        ]);

        Notification::factory()->create([
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'marketplace' => 'trendyol',
            'source' => 'invoice',
            'type' => 'critical',
            'title' => 'Invoice failed',
            'body' => 'fail',
            'created_at' => now()->subMinutes(10),
        ]);

        app(IntegrationHealthNotifier::class)->notifyTenant($user->id);

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $user->id,
            'marketplace' => 'trendyol',
            'type' => 'critical',
            'dedupe_key' => 'health:'.$user->id.':trendyol:down',
            'channel' => 'in_app',
        ]);
    }

    public function test_down_is_deduped_within_window(): void
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
            'source' => 'order_sync',
            'type' => 'critical',
            'title' => 'Order failed',
            'body' => 'fail',
            'created_at' => now()->subMinutes(5),
        ]);

        app(IntegrationHealthNotifier::class)->notifyTenant($user->id);
        app(IntegrationHealthNotifier::class)->notifyTenant($user->id);

        $this->assertSame(1, Notification::query()
            ->where('tenant_id', $user->id)
            ->where('marketplace', 'hb')
            ->where('dedupe_key', 'health:'.$user->id.':hb:down')
            ->where('channel', 'in_app')
            ->count());
    }

    public function test_degraded_creates_operational_notification(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

        $user = $this->makeSubscribedUser();
        $marketplace = $this->seedMarketplace('n11', 'N11');
        MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
        ]);

        $product = $this->makeProduct($user->id, 'SKU-OP-'.Str::uuid());
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

        app(IntegrationHealthNotifier::class)->notifyTenant($user->id);

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $user->id,
            'marketplace' => 'n11',
            'type' => 'operational',
            'dedupe_key' => 'health:'.$user->id.':n11:degraded',
            'channel' => 'in_app',
        ]);
    }

    public function test_recovery_creates_info_notification(): void
    {
        $user = $this->makeSubscribedUser();
        $marketplace = $this->seedMarketplace('ty', 'Trendyol');
        MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

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

        Carbon::setTestNow(Carbon::create(2026, 2, 6, 12, 0, 0));

        $product = $this->makeProduct($user->id, 'SKU-REC-'.Str::uuid());
        MarketplaceProduct::create([
            'product_id' => $product->id,
            'marketplace_id' => $marketplace->id,
            'price' => 120,
            'stock_quantity' => 5,
            'last_sync_at' => now()->subMinutes(5),
        ]);

        app(IntegrationHealthNotifier::class)->notifyTenant($user->id);

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $user->id,
            'marketplace' => 'ty',
            'type' => 'operational',
            'channel' => 'in_app',
        ]);
    }

    public function test_tenant_isolation(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 5, 10, 0, 0));

        $userA = $this->makeSubscribedUser();
        $userB = $this->makeSubscribedUser();
        $marketplace = $this->seedMarketplace('mz', 'MockZ');

        MarketplaceCredential::create([
            'user_id' => $userB->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
        ]);

        Notification::factory()->create([
            'tenant_id' => $userB->id,
            'user_id' => $userB->id,
            'marketplace' => 'mz',
            'source' => 'invoice',
            'type' => 'critical',
            'title' => 'Invoice failed',
            'body' => 'fail',
            'created_at' => now()->subMinutes(10),
        ]);

        app(IntegrationHealthNotifier::class)->notifyTenant($userA->id);
        app(IntegrationHealthNotifier::class)->notifyTenant($userB->id);

        $this->assertDatabaseMissing('app_notifications', [
            'tenant_id' => $userA->id,
            'marketplace' => 'mz',
            'dedupe_key' => 'health:'.$userA->id.':mz:down',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'tenant_id' => $userB->id,
            'marketplace' => 'mz',
            'dedupe_key' => 'health:'.$userB->id.':mz:down',
            'channel' => 'in_app',
        ]);
    }
}
