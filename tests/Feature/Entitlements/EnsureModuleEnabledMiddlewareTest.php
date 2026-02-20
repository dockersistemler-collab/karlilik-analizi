<?php

namespace Tests\Feature\Entitlements;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Module;
use App\Models\UserModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureModuleEnabledMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'module:feature.api_access'])
            ->get('/__test/module', fn () => response('ok'))
            ->name('test.module');

        Route::middleware(['web', 'module:feature.sub_users'])
            ->get('/__test/sub-users-module', fn () => response('ok'))
            ->name('test.sub-users-module');
    }

    public function test_redirects_to_plan_purchase_on_web_requests_when_plan_is_missing(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)
            ->get('/__test/module')
            ->assertRedirect(route('portal.billing.plans'))
            ->assertSessionHas('warning');
    }

    public function test_returns_403_on_json_requests_when_plan_is_missing(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $this->actingAs($user)
            ->getJson('/__test/module')
            ->assertStatus(403)
            ->assertJson([
                'error' => 'PLAN_REQUIRED',
            ]);
    }

    public function test_redirects_to_upsell_on_web_requests_when_plan_exists_but_module_is_missing(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $plan = Plan::create([
            'name' => 'Basic',
            'slug' => 'basic-module-mw',
            'price' => 10,
            'billing_period' => 'monthly',
            'features' => [
                'modules' => [],
            ],
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
        $user->refresh();

        $this->actingAs($user)
            ->get('/__test/module')
            ->assertRedirect(route('portal.modules.upsell', ['code' => 'feature.api_access']))
            ->assertSessionHas('info');
    }

    public function test_returns_403_on_json_requests_when_plan_exists_but_module_is_missing(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $plan = Plan::create([
            'name' => 'Basic',
            'slug' => 'basic-module-mw-json',
            'price' => 10,
            'billing_period' => 'monthly',
            'features' => [
                'modules' => [],
            ],
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
        $user->refresh();

        $this->actingAs($user)
            ->getJson('/__test/module')
            ->assertStatus(403)
            ->assertJson([
                'error' => 'MODULE_NOT_ENABLED',
                'module' => 'feature.api_access',
            ]);
    }

    public function test_sub_users_module_requires_user_module_even_if_plan_has_it(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $plan = Plan::create([
            'name' => 'Plan With Sub Users',
            'slug' => 'plan-with-sub-users',
            'price' => 10,
            'billing_period' => 'monthly',
            'features' => [
                'modules' => ['feature.sub_users'],
            ],
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

        Module::create([
            'code' => 'feature.sub_users',
            'name' => 'Alt Kullanicilar',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->get('/__test/sub-users-module')
            ->assertRedirect(route('portal.modules.upsell', ['code' => 'feature.sub_users']));
    }

    public function test_sub_users_module_allows_access_when_user_module_is_active(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $plan = Plan::create([
            'name' => 'Plan With Sub Users Active',
            'slug' => 'plan-with-sub-users-active',
            'price' => 10,
            'billing_period' => 'monthly',
            'features' => [
                'modules' => ['feature.sub_users'],
            ],
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

        $module = Module::create([
            'code' => 'feature.sub_users',
            'name' => 'Alt Kullanicilar',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        UserModule::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)
            ->get('/__test/sub-users-module')
            ->assertOk();
    }
}

