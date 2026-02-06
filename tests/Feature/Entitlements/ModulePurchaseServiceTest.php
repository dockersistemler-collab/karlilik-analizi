<?php

namespace Tests\Feature\Entitlements;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\User;
use App\Models\UserModule;
use App\Services\Purchases\ModulePurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ModulePurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_paid_grants_user_module(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.einvoice',
            'name' => 'E-Fatura',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $purchase = ModulePurchase::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'manual',
            'provider_payment_id' => null,
            'amount' => 99.90,
            'currency' => 'TRY',
            'period' => 'monthly',
            'status' => 'pending',
            'meta' => ['source' => 'test'],
        ]);

        $service = app(ModulePurchaseService::class);
        $service->markPaid($purchase);

        $this->assertDatabaseHas('user_modules', [
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => 'active',
        ]);
    }

    public function test_mark_paid_extends_from_existing_active_end_date_without_losing_time(): void
    {
        $now = Carbon::create(2026, 1, 1, 10, 0, 0, 'UTC');
        Carbon::setTestNow($now);

        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.exports',
            'name' => 'Exportlar',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $existingEndsAt = $now->copy()->addDays(10);

        UserModule::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => 'active',
            'starts_at' => $now->copy()->subDays(20),
            'ends_at' => $existingEndsAt,
            'meta' => null,
        ]);

        $purchase = ModulePurchase::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'manual',
            'provider_payment_id' => null,
            'amount' => 99.90,
            'currency' => 'TRY',
            'period' => 'monthly',
            'status' => 'pending',
            'meta' => ['source' => 'test'],
        ]);

        $service = app(ModulePurchaseService::class);
        $service->markPaid($purchase);

        $purchase->refresh();
        $this->assertSame($existingEndsAt->toDateTimeString(), $purchase->starts_at?->toDateTimeString());
        $this->assertSame($existingEndsAt->copy()->addMonth()->toDateString(), $purchase->ends_at?->toDateString());

        $userModule = $user->userModules()->where('module_id', $module->id)->first();
        $this->assertNotNull($userModule);
        $this->assertSame('active', $userModule->status);
        $this->assertSame($existingEndsAt->copy()->addMonth()->toDateString(), $userModule->ends_at?->toDateString());
    }

    public function test_mark_cancelled_does_not_revoke_user_module(): void
    {
        $now = Carbon::create(2026, 1, 1, 10, 0, 0, 'UTC');
        Carbon::setTestNow($now);

        $user = User::factory()->create(['role' => 'client']);

        $module = Module::create([
            'code' => 'feature.reports',
            'name' => 'Raporlar',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $userModule = UserModule::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => 'active',
            'starts_at' => $now->copy()->subDays(5),
            'ends_at' => $now->copy()->addDays(5),
            'meta' => null,
        ]);

        $purchase = ModulePurchase::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'provider' => 'manual',
            'provider_payment_id' => null,
            'amount' => 99.90,
            'currency' => 'TRY',
            'period' => 'monthly',
            'status' => 'pending',
            'meta' => ['source' => 'test'],
        ]);

        $service = app(ModulePurchaseService::class);
        $service->markCancelled($purchase);

        $userModule->refresh();
        $this->assertSame('active', $userModule->status);
        $this->assertSame($now->copy()->addDays(5)->toDateString(), $userModule->ends_at?->toDateString());
    }
}
