<?php

namespace Tests\Feature\Api;

use App\Models\EInvoice;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApiTokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubscribedUserWithModule(array $modules): User
    {
        $user = User::factory()->create(['role' => 'client']);

        foreach ($modules as $index => $moduleCode) {
            Module::query()->firstOrCreate(
                ['code' => $moduleCode],
                [
                    'name' => $moduleCode,
                    'description' => null,
                    'type' => str_starts_with($moduleCode, 'integration.') ? 'integration' : 'feature',
                    'billing_type' => 'recurring',
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

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

    public function test_expired_token_returns_401_and_revokes_token(): void
    {
        $user = $this->makeSubscribedUserWithModule(['feature.einvoice_api']);

        EInvoice::create([
            'user_id' => $user->id,
            'source_type' => 'order',
            'source_id' => 1,
            'status' => 'issued',
            'type' => 'sale',
            'currency' => 'TRY',
            'buyer_name' => 'Buyer',
            'subtotal' => 10,
            'tax_total' => 2,
            'discount_total' => 0,
            'grand_total' => 12,
        ]);

        $token = $user->createToken('test', ['einvoices:read'], now()->subMinute());
        $tokenId = $token->accessToken->id;

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/einvoices')
            ->assertStatus(401)
            ->assertJson(['error' => 'TOKEN_EXPIRED']);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_ip_allowlist_mismatch_returns_403(): void
    {
        $user = $this->makeSubscribedUserWithModule(['feature.einvoice_api']);

        $token = $user->createToken('test', ['einvoices:read'], now()->addDays(30));
        $token->accessToken->ip_allowlist_json = ['127.0.0.2'];
        $token->accessToken->save();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/einvoices')
            ->assertStatus(403)
            ->assertJson(['error' => 'IP_NOT_ALLOWED']);
    }

    public function test_audit_log_row_is_created_on_api_call(): void
    {
        $user = $this->makeSubscribedUserWithModule(['feature.einvoice_api']);

        $token = $user->createToken('test', ['einvoices:read'], now()->addDays(30));

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/einvoices')
            ->assertOk();

        $this->assertDatabaseCount('api_audit_logs', 1);
    }

    public function test_rate_limit_returns_429(): void
    {
        $user = $this->makeSubscribedUserWithModule(['feature.einvoice_api']);

        $token = $user->createToken('test', ['einvoices:read'], now()->addDays(30));

        for ($i = 0; $i < 61; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token->plainTextToken,
                'Accept' => 'application/json',
            ])->getJson('/api/v1/einvoices');
        }

        $response->assertStatus(429);
    }
}

