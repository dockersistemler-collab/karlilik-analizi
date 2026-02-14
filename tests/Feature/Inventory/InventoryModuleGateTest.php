<?php

namespace Tests\Feature\Inventory;

use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InventoryModuleGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'module:feature.inventory,404'])
            ->get('/__test/inventory-gate', fn () => response('ok'))
            ->name('test.inventory.gate');
    }

    public function test_returns_404_when_inventory_module_is_inactive(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        Module::query()->create([
            'code' => 'feature.inventory',
            'name' => 'Inventory',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $plan = Plan::create([
            'name' => 'Inventory Plan',
            'slug' => 'inventory-plan-inactive',
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

        $this->actingAs($user)
            ->get('/__test/inventory-gate')
            ->assertNotFound();
    }
}
