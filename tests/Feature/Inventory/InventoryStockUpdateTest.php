<?php

namespace Tests\Feature\Inventory;

use App\Http\Controllers\Admin\InventoryProductController;
use App\Jobs\PushStockToMarketplacesJob;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InventoryStockUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'module:feature.inventory,404'])
            ->put('/__test/inventory/products/{product}', [InventoryProductController::class, 'update'])
            ->name('test.inventory.products.update');
        Route::middleware(['web', 'auth'])
            ->get('/__test/inventory/products', fn () => response('ok'))
            ->name('portal.inventory.admin.products.index');
    }

    public function test_stock_update_creates_movement_alert_and_dispatches_job(): void
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
            'slug' => 'inventory-plan-active',
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

        $product = Product::query()->create([
            'user_id' => $user->id,
            'sku' => 'INV-1',
            'barcode' => '123',
            'name' => 'Test Product',
            'price' => 100,
            'cost_price' => 80,
            'stock_quantity' => 10,
            'critical_stock_level' => 5,
            'currency' => 'TRY',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put('/__test/inventory/products/'.$product->id, [
                'direction' => 'decrease',
                'quantity' => 6,
                'critical_stock_level' => 5,
                'note' => 'manual test',
            ])
            ->assertRedirectContains('/admin/inventory/products');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 4,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'manual_adjust',
            'quantity_change' => -6,
        ]);
        $this->assertDatabaseHas('stock_alerts', [
            'product_id' => $product->id,
            'alert_type' => 'critical_stock',
            'is_active' => true,
        ]);

        Queue::assertPushed(PushStockToMarketplacesJob::class);
    }
}
