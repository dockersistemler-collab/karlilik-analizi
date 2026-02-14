<?php

namespace Tests\Feature\Inventory;

use App\Jobs\PushStocksToMarketplaceJob;
use App\Jobs\PushStockToMarketplacesJob;
use App\Models\MarketplaceAccount;
use App\Models\Marketplace;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InventoryMarketplaceSyncActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_marketplace_sync_action_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'client']);
        Module::query()->create([
            'code' => 'feature.inventory',
            'name' => 'Inventory',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $plan = Plan::create([
            'name' => 'Inventory Plan',
            'slug' => 'inventory-sync-plan',
            'price' => 10,
            'billing_period' => 'monthly',
            'features' => ['modules' => ['feature.inventory']],
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 10,
            'billing_period' => 'monthly',
        ]);

        $marketplace = Marketplace::query()->create([
            'name' => 'Amazon TR',
            'code' => 'amazon',
            'is_active' => true,
            'commission_rate' => 0,
        ]);
        MarketplaceAccount::query()->create([
            'tenant_id' => $user->id,
            'marketplace' => 'amazon',
            'connector_key' => 'amazon',
            'store_name' => 'Test Store',
            'credentials' => ['token' => 'dummy'],
            'credentials_json' => ['token' => 'dummy'],
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('portal.inventory.admin.sync-marketplace'), [
                'sync_scope' => 'single',
                'marketplace_id' => $marketplace->id,
            ])
            ->assertRedirect();

        Queue::assertPushed(PushStocksToMarketplaceJob::class, function ($job) use ($user) {
            return (int) $job->tenantId === (int) $user->id
                && $job->marketplaceCode === 'amazon';
        });
    }

    public function test_all_scope_dispatches_jobs_for_all_active_marketplaces(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'client']);
        Module::query()->create([
            'code' => 'feature.inventory',
            'name' => 'Inventory',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $plan = Plan::create([
            'name' => 'Inventory Plan',
            'slug' => 'inventory-sync-plan-all',
            'price' => 10,
            'billing_period' => 'monthly',
            'features' => ['modules' => ['feature.inventory']],
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 10,
            'billing_period' => 'monthly',
        ]);

        Marketplace::query()->create([
            'name' => 'Amazon TR',
            'code' => 'amazon',
            'is_active' => true,
            'commission_rate' => 0,
        ]);
        MarketplaceAccount::query()->create([
            'tenant_id' => $user->id,
            'marketplace' => 'amazon',
            'connector_key' => 'amazon',
            'store_name' => 'Test Store Amazon',
            'credentials' => ['token' => 'dummy'],
            'credentials_json' => ['token' => 'dummy'],
            'status' => 'active',
            'is_active' => true,
        ]);
        Marketplace::query()->create([
            'name' => 'Trendyol',
            'code' => 'trendyol',
            'is_active' => true,
            'commission_rate' => 0,
        ]);
        MarketplaceAccount::query()->create([
            'tenant_id' => $user->id,
            'marketplace' => 'trendyol',
            'connector_key' => 'trendyol',
            'store_name' => 'Test Store Trendyol',
            'credentials' => ['token' => 'dummy'],
            'credentials_json' => ['token' => 'dummy'],
            'status' => 'active',
            'is_active' => true,
        ]);
        Marketplace::query()->create([
            'name' => 'N11',
            'code' => 'n11',
            'is_active' => false,
            'commission_rate' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('portal.inventory.admin.sync-marketplace'), [
                'sync_scope' => 'all',
            ])
            ->assertRedirect();

        Queue::assertPushed(PushStocksToMarketplaceJob::class, 2);
        Queue::assertPushed(PushStocksToMarketplaceJob::class, function ($job) use ($user) {
            return (int) $job->tenantId === (int) $user->id
                && in_array($job->marketplaceCode, ['amazon', 'trendyol'], true);
        });
    }

    public function test_selected_scope_requires_api_connection_for_selected_marketplace(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'client']);
        Module::query()->create([
            'code' => 'feature.inventory',
            'name' => 'Inventory',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $plan = Plan::create([
            'name' => 'Inventory Plan Selected',
            'slug' => 'inventory-sync-plan-selected',
            'price' => 10,
            'billing_period' => 'monthly',
            'features' => ['modules' => ['feature.inventory']],
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 10,
            'billing_period' => 'monthly',
        ]);

        $marketplace = Marketplace::query()->create([
            'name' => 'Amazon TR',
            'code' => 'amazon',
            'is_active' => true,
            'commission_rate' => 0,
        ]);
        MarketplaceAccount::query()->create([
            'tenant_id' => $user->id,
            'marketplace' => 'amazon',
            'connector_key' => 'amazon',
            'store_name' => 'No Credential Store',
            'credentials' => [],
            'credentials_json' => [],
            'status' => 'active',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'user_id' => $user->id,
            'sku' => 'INV-TST-SEL-001',
            'barcode' => '8690000000011',
            'name' => 'Selected Sync Product',
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('portal.inventory.admin.sync-marketplace'), [
                'sync_scope' => 'selected',
                'marketplace_id' => $marketplace->id,
                'selected_product_ids_csv' => (string) $product->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Bu pazaryeri icin API baglantisi gerekli.');

        Queue::assertNothingPushed();
    }

    public function test_assign_marketplace_requires_api_connection(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'client']);
        Module::query()->create([
            'code' => 'feature.inventory',
            'name' => 'Inventory',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $plan = Plan::create([
            'name' => 'Inventory Plan Open',
            'slug' => 'inventory-open-plan',
            'price' => 10,
            'billing_period' => 'monthly',
            'features' => ['modules' => ['feature.inventory']],
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 10,
            'billing_period' => 'monthly',
        ]);

        $marketplace = Marketplace::query()->create([
            'name' => 'Trendyol',
            'code' => 'trendyol',
            'is_active' => true,
            'commission_rate' => 0,
        ]);
        MarketplaceAccount::query()->create([
            'tenant_id' => $user->id,
            'marketplace' => 'trendyol',
            'connector_key' => 'trendyol',
            'store_name' => 'No API Store',
            'credentials' => [],
            'credentials_json' => [],
            'status' => 'active',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'user_id' => $user->id,
            'sku' => 'INV-TST-OPEN-001',
            'barcode' => '8690000000012',
            'name' => 'Open Marketplace Product',
            'price' => 120,
            'stock_quantity' => 7,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('portal.inventory.admin.assign-marketplace'), [
                'marketplace_id' => $marketplace->id,
                'selected_product_ids_csv' => (string) $product->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Bu pazaryeri icin API baglantisi gerekli.');
    }

    public function test_selected_scope_without_marketplace_dispatches_jobs_when_any_api_exists(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'client']);
        Module::query()->create([
            'code' => 'feature.inventory',
            'name' => 'Inventory',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $plan = Plan::create([
            'name' => 'Inventory Plan Missing Marketplace',
            'slug' => 'inventory-sync-plan-missing-marketplace',
            'price' => 10,
            'billing_period' => 'monthly',
            'features' => ['modules' => ['feature.inventory']],
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 10,
            'billing_period' => 'monthly',
        ]);

        Marketplace::query()->create([
            'name' => 'Amazon TR',
            'code' => 'amazon',
            'is_active' => true,
            'commission_rate' => 0,
        ]);
        MarketplaceAccount::query()->create([
            'tenant_id' => $user->id,
            'marketplace' => 'amazon',
            'connector_key' => 'amazon',
            'store_name' => 'Any API Store',
            'credentials' => ['token' => 'dummy'],
            'credentials_json' => ['token' => 'dummy'],
            'status' => 'active',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'user_id' => $user->id,
            'sku' => 'INV-TST-NOMP-001',
            'barcode' => '8690000000013',
            'name' => 'No Marketplace Selected Product',
            'price' => 130,
            'stock_quantity' => 9,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('portal.inventory.admin.sync-marketplace'), [
                'sync_scope' => 'selected',
                'marketplace_id' => null,
                'selected_product_ids_csv' => (string) $product->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Secili urunler icin stok gonderimi kuyruga alindi.');

        Queue::assertPushed(PushStockToMarketplacesJob::class, function ($job) use ($user, $product) {
            return (int) $job->tenantId === (int) $user->id
                && (int) $job->productId === (int) $product->id;
        });
    }
}
