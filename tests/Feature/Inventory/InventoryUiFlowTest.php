<?php

namespace Tests\Feature\Inventory;

use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryUiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_routes_and_menu_are_hidden_when_module_is_inactive(): void
    {
        $user = $this->makeClientWithInventoryPlan();
        Module::query()->create([
            'code' => 'feature.inventory',
            'name' => 'Inventory',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $inventoryUrl = route('portal.inventory.admin.products.index');
        $dashboardUrl = route('portal.dashboard');

        $this->actingAs($user)
            ->get($inventoryUrl)
            ->assertNotFound();

        $this->actingAs($user)
            ->get($dashboardUrl)
            ->assertOk()
            ->assertDontSee('/admin/inventory/products', false)
            ->assertDontSee('/user/inventory/products', false);
    }

    public function test_inventory_routes_and_menu_are_visible_when_module_is_active(): void
    {
        $user = $this->makeClientWithInventoryPlan();
        Module::query()->create([
            'code' => 'feature.inventory',
            'name' => 'Inventory',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $inventoryUrl = route('portal.inventory.admin.products.index');
        $dashboardUrl = route('portal.dashboard');

        $this->actingAs($user)
            ->get($inventoryUrl)
            ->assertOk();

        $this->actingAs($user)
            ->get($dashboardUrl)
            ->assertOk()
            ->assertSee('/admin/inventory/products', false);
    }

    private function makeClientWithInventoryPlan(): User
    {
        $user = User::factory()->create([
            'role' => 'client',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Inventory Plan',
            'slug' => 'inventory-ui-plan-' . uniqid(),
            'price' => 99,
            'billing_period' => 'monthly',
            'features' => [
                'modules' => ['feature.inventory'],
            ],
            'is_active' => true,
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 99,
            'billing_period' => 'monthly',
        ]);

        return $user->fresh();
    }
}
