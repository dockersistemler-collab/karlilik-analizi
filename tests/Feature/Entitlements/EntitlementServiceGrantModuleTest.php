<?php

namespace Tests\Feature\Entitlements;

use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EntitlementServiceGrantModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_grant_module_creates_active_user_module(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $this->attachActivePlan($user);

        $module = Module::create([
            'code' => 'feature.einvoice',
            'name' => 'E-Fatura',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = app(EntitlementService::class);
        $um = $service->grantModule($user, $module->code);

        $this->assertSame('active', $um->status);
        $this->assertNotNull($um->starts_at);
        $this->assertDatabaseCount('user_modules', 1);
    }

    public function test_grant_module_is_idempotent_and_does_not_duplicate(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $this->attachActivePlan($user);

        $module = Module::create([
            'code' => 'feature.einvoice',
            'name' => 'E-Fatura',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = app(EntitlementService::class);
        $service->grantModule($user, $module->code);
        $service->grantModule($user, $module->code);

        $this->assertDatabaseCount('user_modules', 1);
    }

    public function test_grant_module_extends_ends_at_when_new_ends_at_is_later(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $this->attachActivePlan($user);

        $module = Module::create([
            'code' => 'feature.einvoice',
            'name' => 'E-Fatura',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = app(EntitlementService::class);
        $firstEndsAt = Carbon::now()->addDay();
        $secondEndsAt = Carbon::now()->addDays(10);

        $um1 = $service->grantModule($user, $module->code, $firstEndsAt);
        $um2 = $service->grantModule($user, $module->code, $secondEndsAt);

        
        $this->assertSame($um1->id, $um2->id);
    }

    public function test_grant_module_merges_meta(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $this->attachActivePlan($user);

        $module = Module::create([
            'code' => 'feature.einvoice',
            'name' => 'E-Fatura',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $service = app(EntitlementService::class);
        $service->grantModule($user, $module->code, null, ['a' => 1, 'b' => 2]);
        $um = $service->grantModule($user, $module->code, null, ['b' => 3, 'c' => 4]);

        $this->assertSame(['a' => 1, 'b' => 3, 'c' => 4], $um->meta);
    }

    private function attachActivePlan(User $user): void
    {
        $plan = Plan::create([
            'name' => 'Basic',
            'slug' => 'basic-entitlements-grant',
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
    }
}

