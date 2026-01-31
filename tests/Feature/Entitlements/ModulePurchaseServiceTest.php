<?php

namespace Tests\Feature\Entitlements;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\User;
use App\Services\Purchases\ModulePurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

