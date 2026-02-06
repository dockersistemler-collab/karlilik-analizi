<?php

namespace Tests\Feature\EInvoices;

use App\Models\EInvoice;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\EInvoices\EInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_draft_from_order_creates_einvoice_and_items(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $order = Order::create([
            'user_id' => $user->id,
            'marketplace_id' => null,
            'marketplace_order_id' => 'MP-1',
            'order_number' => 'ORD-1',
            'status' => 'approved',
            'total_amount' => 100,
            'commission_amount' => 0,
            'net_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Test Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '+905350000000',
            'shipping_address' => 'Ship Addr',
            'billing_address' => 'Bill Addr',
            'cargo_company' => null,
            'tracking_number' => null,
            'order_date' => now(),
            'approved_at' => now(),
            'shipped_at' => null,
            'delivered_at' => null,
            'items' => [
                ['sku' => 'SKU1', 'name' => 'Ürün 1', 'quantity' => 2, 'price' => 10, 'vat_rate' => 20],
                ['barcode' => 'B2', 'title' => 'Ürün 2', 'qty' => 1, 'unit_price' => 20],
            ],
            'marketplace_data' => [],
        ]);

        $service = app(EInvoiceService::class);
        $einvoice = $service->createDraftFromOrder($order);

        $this->assertInstanceOf(EInvoice::class, $einvoice);
        $this->assertSame('draft', $einvoice->status);
        $this->assertSame('order', $einvoice->source_type);
        $this->assertSame($order->id, $einvoice->source_id);

        $this->assertDatabaseHas('e_invoices', [
            'id' => $einvoice->id,
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseCount('e_invoice_items', 2);
        $this->assertDatabaseHas('e_invoice_events', [
            'einvoice_id' => $einvoice->id,
            'type' => 'created_draft',
        ]);
    }

    public function test_create_draft_from_order_does_not_duplicate_existing(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $order = Order::create([
            'user_id' => $user->id,
            'marketplace_id' => null,
            'marketplace_order_id' => 'MP-2',
            'order_number' => 'ORD-2',
            'status' => 'approved',
            'total_amount' => 100,
            'commission_amount' => 0,
            'net_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Test Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '+905350000000',
            'shipping_address' => 'Ship Addr',
            'billing_address' => 'Bill Addr',
            'order_date' => now(),
            'items' => [
                ['sku' => 'SKU1', 'name' => 'Ürün 1', 'quantity' => 1, 'price' => 10],
            ],
            'marketplace_data' => [],
        ]);

        $service = app(EInvoiceService::class);
        $first = $service->createDraftFromOrder($order);
        $second = $service->createDraftFromOrder($order);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('e_invoices', 1);
        $this->assertDatabaseCount('e_invoice_items', 1);
    }

    public function test_issue_generates_invoice_no_and_sets_pdf_path(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => 'client']);

        $order = Order::create([
            'user_id' => $user->id,
            'marketplace_id' => null,
            'marketplace_order_id' => 'MP-3',
            'order_number' => 'ORD-3',
            'status' => 'approved',
            'total_amount' => 100,
            'commission_amount' => 0,
            'net_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Test Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '+905350000000',
            'shipping_address' => 'Ship Addr',
            'billing_address' => 'Bill Addr',
            'order_date' => now(),
            'items' => [
                ['sku' => 'SKU1', 'name' => 'Ürün 1', 'quantity' => 1, 'price' => 10],
            ],
            'marketplace_data' => [],
        ]);

        $service = app(EInvoiceService::class);
        $einvoice = $service->createDraftFromOrder($order);
        $issued = $service->issue($einvoice);

        $this->assertSame('issued', $issued->status);
        $this->assertNotNull($issued->invoice_no);
        $this->assertNotNull($issued->pdf_path);
        Storage::disk('local')->assertExists($issued->pdf_path);

        $bytes = Storage::disk('local')->get($issued->pdf_path);
        $this->assertIsString($bytes);
        $this->assertSame('%PDF', substr($bytes, 0, 4));
    }

    public function test_module_gating_redirects_to_upsell_when_feature_not_enabled(): void
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
            'features' => ['modules' => []],
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

        $this->actingAs($user)
            ->get(route('portal.einvoices.index'))
            ->assertRedirect(route('portal.modules.upsell', ['code' => 'feature.einvoice']));
    }
}

