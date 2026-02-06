<?php

namespace Tests\Feature\Settings;

use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApiAccessUpsellTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubscribedUserWithModules(array $modules): User
    {
        $user = User::factory()->create(['role' => 'client']);

        $plan = Plan::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => null,
            'price' => 1,
            'yearly_price' => 10,
            'billing_period' => 'monthly',
            'max_products' => 0,
            'max_marketplaces' => 0,
            'max_orders_per_month' => 0,
            'max_tickets_per_month' => 0,
            'api_access' => false,
            'advanced_reports' => false,
            'priority_support' => false,
            'custom_integrations' => false,
            'features' => ['modules' => $modules],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addMonth(),
            'cancelled_at' => null,
            'amount' => 1,
            'billing_period' => 'monthly',
            'auto_renew' => true,
            'current_products_count' => 0,
            'current_marketplaces_count' => 0,
            'current_month_orders_count' => 0,
            'usage_reset_at' => Carbon::now()->addMonth(),
        ]);

        return $user;
    }

    public function test_api_settings_shows_upsell_when_user_does_not_have_entitlement(): void
    {
        Module::create([
            'code' => 'feature.einvoice_api',
            'name' => 'E-Fatura API Erişimi',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = $this->makeSubscribedUserWithModules([]);

        $this->actingAs($user)
            ->get(route('portal.settings.api'))
            ->assertOk()
            ->assertSee('E-Fatura API Erişimi');
    }

    public function test_api_token_create_works_when_entitled(): void
    {
        Module::create([
            'code' => 'feature.einvoice_api',
            'name' => 'E-Fatura API Erişimi',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = $this->makeSubscribedUserWithModules(['feature.einvoice_api']);

        $this->actingAs($user)
            ->post(route('portal.settings.api.tokens.store'), [
                'name' => 'test',
                'abilities' => ['einvoices:read'],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }
}


