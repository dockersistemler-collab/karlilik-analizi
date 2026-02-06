<?php

namespace Tests\Feature\Api;

use App\Models\EInvoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EInvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubscribedUserWithModule(array $modules): User
    {
        $user = User::factory()->create(['role' => 'client']);

        $plan = Plan::create([
            'name' => 'Test',
            'slug' => 'test-'.Str::random(8),
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

    public function test_token_can_list_einvoices(): void
    {
        $user = $this->makeSubscribedUserWithModule(['feature.einvoice_api']);
        Sanctum::actingAs($user, ['einvoices:read']);

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

        $this->getJson('/api/v1/einvoices')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_cannot_access_other_users_invoice(): void
    {
        $user1 = $this->makeSubscribedUserWithModule(['feature.einvoice_api']);
        $user2 = $this->makeSubscribedUserWithModule(['feature.einvoice_api']);

        $inv = EInvoice::create([
            'user_id' => $user2->id,
            'source_type' => 'order',
            'source_id' => 2,
            'status' => 'issued',
            'type' => 'sale',
            'currency' => 'TRY',
            'buyer_name' => 'Buyer',
        ]);

        Sanctum::actingAs($user1, ['einvoices:read']);

        $this->getJson("/api/v1/einvoices/{$inv->id}")
            ->assertStatus(404);
    }

    public function test_provider_status_requires_ability(): void
    {
        $user = $this->makeSubscribedUserWithModule(['feature.einvoice_api']);

        $inv = EInvoice::create([
            'user_id' => $user->id,
            'source_type' => 'order',
            'source_id' => 3,
            'status' => 'issued',
            'type' => 'sale',
            'currency' => 'TRY',
            'buyer_name' => 'Buyer',
        ]);

        Sanctum::actingAs($user, ['einvoices:read']);
        $this->postJson("/api/v1/einvoices/{$inv->id}/provider-status", [
            'provider_status' => 'SENT',
        ])->assertStatus(403);
    }

    public function test_provider_status_update_creates_event_log(): void
    {
        $user = $this->makeSubscribedUserWithModule(['feature.einvoice_api']);

        $inv = EInvoice::create([
            'user_id' => $user->id,
            'source_type' => 'order',
            'source_id' => 4,
            'status' => 'issued',
            'type' => 'sale',
            'currency' => 'TRY',
            'buyer_name' => 'Buyer',
        ]);

        Sanctum::actingAs($user, ['einvoices:status']);

        $this->postJson("/api/v1/einvoices/{$inv->id}/provider-status", [
            'provider_status' => 'SENT',
            'raw' => ['hello' => 'world'],
        ])->assertOk();

        $this->assertDatabaseHas('e_invoice_events', [
            'einvoice_id' => $inv->id,
            'type' => 'provider_status_updated',
        ]);
    }
}
