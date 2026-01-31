<?php

namespace Tests\Feature\Entitlements;

use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserModule;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_module_returns_true_when_plan_has_module_is_true(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 100,
            'billing_period' => 'monthly',
            'features' => [
                'modules' => ['feature.api_access'],
            ],
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 100,
            'billing_period' => 'monthly',
        ]);
        $user->refresh();

        $service = app(EntitlementService::class);
        $this->assertTrue($service->hasModule($user, 'feature.api_access'));
    }

    public function test_has_module_returns_true_when_user_module_is_active(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $plan = Plan::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price' => 10,
            'billing_period' => 'monthly',
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

        $module = Module::create([
            'code' => 'feature.some_feature',
            'name' => 'Some Feature',
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
            'ends_at' => now()->addDay(),
        ]);

        $service = app(EntitlementService::class);
        $this->assertTrue($service->hasModule($user, 'feature.some_feature'));
    }

    public function test_has_module_returns_false_when_user_module_is_expired_by_ends_at(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $plan = Plan::create([
            'name' => 'Basic',
            'slug' => 'basic2',
            'price' => 10,
            'billing_period' => 'monthly',
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addMonth(),
            'amount' => 10,
            'billing_period' => 'monthly',
        ]);
        $user->refresh();

        $module = Module::create([
            'code' => 'feature.expiring',
            'name' => 'Expiring Feature',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        UserModule::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);

        $service = app(EntitlementService::class);
        $this->assertFalse($service->hasModule($user, 'feature.expiring'));
    }

    public function test_integration_modules_can_be_enabled_via_plan_modules(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $plan = Plan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price' => 500,
            'billing_period' => 'monthly',
            'features' => [
                'modules' => ['integration.trendyol'],
            ],
        ]);
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 500,
            'billing_period' => 'monthly',
        ]);
        $user->refresh();

        $service = app(EntitlementService::class);
        $this->assertTrue($service->hasModule($user, 'integration.trendyol'));
    }
}
