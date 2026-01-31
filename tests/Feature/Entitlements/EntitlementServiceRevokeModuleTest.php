<?php

namespace Tests\Feature\Entitlements;

use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementServiceRevokeModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_revoke_module_makes_has_module_false(): void
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
        $this->assertTrue($service->hasModule($user, $module->code));

        $service->revokeModule($user, $module->code);
        $this->assertFalse($service->hasModule($user, $module->code));
    }

    public function test_revoke_module_hard_delete_removes_row(): void
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
        $this->assertDatabaseCount('user_modules', 1);

        $service->revokeModule($user, $module->code, true);
        $this->assertDatabaseCount('user_modules', 0);
    }

    private function attachActivePlan(User $user): void
    {
        $plan = Plan::create([
            'name' => 'Basic',
            'slug' => 'basic-entitlements-revoke',
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

