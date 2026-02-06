<?php

namespace Tests\Feature\Settings;

use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiTokenCreateSecurityFieldsTest extends TestCase
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

    public function test_token_create_saves_expires_at_and_ip_allowlist_json(): void
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
                'expires_in_days' => 30,
                'ip_allowlist' => "127.0.0.1\n10.0.0.0/8\n",
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $row = DB::table('personal_access_tokens')->where('name', 'test')->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->expires_at);
        $allow = json_decode((string) $row->ip_allowlist_json, true);
        $this->assertIsArray($allow);
        $this->assertContains('127.0.0.1', $allow);
        $this->assertContains('10.0.0.0/8', $allow);
    }
}

