<?php

namespace Tests\Feature\EInvoices;

use App\Models\EInvoice;
use App\Models\EInvoiceSetting;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\EInvoices\EInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EInvoiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_to_provider_with_null_provider_sets_sent_status_and_provider_fields(): void
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
            'features' => ['modules' => ['feature.einvoice']],
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

        EInvoiceSetting::create([
            'user_id' => $user->id,
            'active_provider_key' => 'null',
            'auto_draft_enabled' => false,
            'auto_issue_enabled' => false,
            'draft_on_status' => 'approved',
            'issue_on_status' => 'shipped',
            'prefix' => 'EA',
            'default_vat_rate' => 20,
        ]);

        $einvoice = EInvoice::create([
            'user_id' => $user->id,
            'source_type' => 'order',
            'source_id' => 1,
            'status' => 'issued',
            'type' => 'sale',
            'currency' => 'TRY',
            'buyer_name' => 'Buyer',
            'subtotal' => 10,
            'tax_total' => 2,
            'grand_total' => 12,
        ]);

        $einvoice->items()->create([
            'sku' => 'SKU1',
            'name' => 'Ürün 1',
            'quantity' => 1,
            'unit_price' => 10,
            'vat_rate' => 20,
            'vat_amount' => 2,
            'discount_amount' => 0,
            'total' => 10,
        ]);

        $service = app(EInvoiceService::class);
        $sent = $service->sendToProvider($einvoice);

        $sent->refresh();
        $this->assertSame('sent', $sent->status);
        $this->assertSame('null', $sent->provider);
        $this->assertStringStartsWith('local:', (string) $sent->provider_invoice_id);
        $this->assertNotNull($sent->provider_status);
        $this->assertDatabaseHas('e_invoice_events', [
            'einvoice_id' => $sent->id,
            'type' => 'provider_sent',
        ]);
    }

    public function test_send_to_provider_throws_403_when_provider_module_missing(): void
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
            'features' => ['modules' => ['feature.einvoice']], // no integration.einvoice.custom
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

        EInvoiceSetting::create([
            'user_id' => $user->id,
            'active_provider_key' => 'custom',
            'auto_draft_enabled' => false,
            'auto_issue_enabled' => false,
            'draft_on_status' => 'approved',
            'issue_on_status' => 'shipped',
            'prefix' => 'EA',
            'default_vat_rate' => 20,
        ]);

        $einvoice = EInvoice::create([
            'user_id' => $user->id,
            'source_type' => 'order',
            'source_id' => 2,
            'status' => 'issued',
            'type' => 'sale',
            'currency' => 'TRY',
            'buyer_name' => 'Buyer',
        ]);

        $service = app(EInvoiceService::class);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $service->sendToProvider($einvoice);
    }
}
