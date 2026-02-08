<?php

namespace Tests\Feature\Profitability;

use App\Models\MarketplaceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureClientOrSubUser;
use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Middleware\EnsureSubUserPermission;
use App\Http\Middleware\EnsureSupportViewReadOnly;
use App\Http\Middleware\EnsureModuleEnabled;
use Tests\TestCase;

class ProfitabilityAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_crud_flow(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $this->withoutMiddleware([
                EnsureClientOrSubUser::class,
                EnsureActiveSubscription::class,
                EnsureSubUserPermission::class,
                EnsureSupportViewReadOnly::class,
                EnsureModuleEnabled::class,
                EnsureEmailIsVerified::class,
            ])
            ->actingAs($user)
            ->post(route('portal.profitability.accounts.store'), [
                'marketplace' => 'trendyol',
                'store_name' => 'Test Store',
                'credentials_json' => '{"api_key":"x","api_secret":"y","supplier_id":"1"}',
                'status' => 'active',
            ])->assertRedirect();

        $account = MarketplaceAccount::query()->where('tenant_id', $user->id)->first();
        $this->assertNotNull($account);

        $this->withoutMiddleware([
                EnsureClientOrSubUser::class,
                EnsureActiveSubscription::class,
                EnsureSubUserPermission::class,
                EnsureSupportViewReadOnly::class,
                EnsureModuleEnabled::class,
                EnsureEmailIsVerified::class,
            ])
            ->actingAs($user)
            ->put(route('portal.profitability.accounts.update', $account), [
                'marketplace' => 'trendyol',
                'store_name' => 'Updated Store',
                'credentials_json' => '{"api_key":"x","api_secret":"y","supplier_id":"1","is_test":true}',
                'status' => 'inactive',
            ])->assertRedirect();

        $account->refresh();
        $this->assertSame('Updated Store', $account->store_name);
        $this->assertSame('inactive', $account->status);

        $this->withoutMiddleware([
                EnsureClientOrSubUser::class,
                EnsureActiveSubscription::class,
                EnsureSubUserPermission::class,
                EnsureSupportViewReadOnly::class,
                EnsureModuleEnabled::class,
                EnsureEmailIsVerified::class,
            ])
            ->actingAs($user)
            ->delete(route('portal.profitability.accounts.destroy', $account))
            ->assertRedirect();

        $this->assertDatabaseMissing('marketplace_accounts', [
            'id' => $account->id,
        ]);
    }
}
