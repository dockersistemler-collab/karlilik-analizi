<?php

namespace Tests\Feature\EInvoices;

use App\Events\OrderStatusChanged;
use App\Jobs\ProcessEInvoiceAutomationJob;
use App\Models\EInvoiceSetting;
use App\Models\Order;
use App\Models\User;
use App\Services\EInvoices\EInvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class EInvoiceAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_maybe_create_draft_creates_when_enabled_and_status_matches(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        EInvoiceSetting::create([
            'user_id' => $user->id,
            'auto_draft_enabled' => true,
            'auto_issue_enabled' => false,
            'draft_on_status' => 'approved',
            'issue_on_status' => 'shipped',
            'prefix' => 'EA',
            'default_vat_rate' => 20,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'marketplace_id' => null,
            'marketplace_order_id' => 'MP-10',
            'order_number' => 'ORD-10',
            'status' => 'approved',
            'total_amount' => 100,
            'commission_amount' => 0,
            'net_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '+905350000000',
            'shipping_address' => 'Ship',
            'billing_address' => 'Bill',
            'order_date' => now(),
            'items' => [
                ['sku' => 'SKU1', 'name' => 'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œrÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¼n 1', 'quantity' => 1, 'price' => 10],
            ],
            'marketplace_data' => [],
        ]);

        $service = app(EInvoiceService::class);
        $invoice = $service->maybeCreateDraftFromOrder($order);

        $this->assertNotNull($invoice);
        $this->assertDatabaseCount('e_invoices', 1);
        $this->assertDatabaseCount('e_invoice_items', 1);
    }

    public function test_maybe_issue_issues_when_enabled_and_status_matches(): void
    {
        Storage::fake('local');
        $pdfMock = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('output')->andReturn('%PDF-1.4 test');

        Pdf::shouldReceive('loadView')
            ->andReturn($pdfMock);

        $now = Carbon::create(2026, 1, 1, 10, 0, 0, 'UTC');
        Carbon::setTestNow($now);

        $user = User::factory()->create(['role' => 'client']);
        EInvoiceSetting::create([
            'user_id' => $user->id,
            'auto_draft_enabled' => true,
            'auto_issue_enabled' => true,
            'draft_on_status' => 'approved',
            'issue_on_status' => 'shipped',
            'prefix' => 'ZZ',
            'default_vat_rate' => 18,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'marketplace_id' => null,
            'marketplace_order_id' => 'MP-11',
            'order_number' => 'ORD-11',
            'status' => 'shipped',
            'total_amount' => 100,
            'commission_amount' => 0,
            'net_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '+905350000000',
            'shipping_address' => 'Ship',
            'billing_address' => 'Bill',
            'order_date' => now(),
            'items' => [
                ['sku' => 'SKU1', 'name' => 'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œrÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¼n 1', 'quantity' => 1, 'price' => 10],
            ],
            'marketplace_data' => [],
        ]);

        $service = app(EInvoiceService::class);
        $invoice = $service->maybeIssueFromOrder($order);

        $this->assertNotNull($invoice);
        $invoice->refresh();
        $this->assertSame('issued', $invoice->status);
        $this->assertNotNull($invoice->invoice_no);
        $this->assertStringStartsWith('ZZ2026-', (string) $invoice->invoice_no);
        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('local')->assertExists($invoice->pdf_path);
    }

    public function test_noop_when_status_does_not_match(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        EInvoiceSetting::create([
            'user_id' => $user->id,
            'auto_draft_enabled' => true,
            'auto_issue_enabled' => true,
            'draft_on_status' => 'approved',
            'issue_on_status' => 'shipped',
            'prefix' => 'EA',
            'default_vat_rate' => 20,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'marketplace_id' => null,
            'marketplace_order_id' => 'MP-12',
            'order_number' => 'ORD-12',
            'status' => 'pending',
            'total_amount' => 100,
            'commission_amount' => 0,
            'net_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '+905350000000',
            'shipping_address' => 'Ship',
            'billing_address' => 'Bill',
            'order_date' => now(),
            'items' => [
                ['sku' => 'SKU1', 'name' => 'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œrÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¼n 1', 'quantity' => 1, 'price' => 10],
            ],
            'marketplace_data' => [],
        ]);

        $service = app(EInvoiceService::class);
        $this->assertNull($service->maybeCreateDraftFromOrder($order));
        $this->assertNull($service->maybeIssueFromOrder($order));
        $this->assertDatabaseCount('e_invoices', 0);
    }

    public function test_duplicate_is_prevented_on_repeated_triggers(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        EInvoiceSetting::create([
            'user_id' => $user->id,
            'auto_draft_enabled' => true,
            'auto_issue_enabled' => false,
            'draft_on_status' => 'approved',
            'issue_on_status' => 'shipped',
            'prefix' => 'EA',
            'default_vat_rate' => 20,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'marketplace_id' => null,
            'marketplace_order_id' => 'MP-13',
            'order_number' => 'ORD-13',
            'status' => 'approved',
            'total_amount' => 100,
            'commission_amount' => 0,
            'net_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '+905350000000',
            'shipping_address' => 'Ship',
            'billing_address' => 'Bill',
            'order_date' => now(),
            'items' => [
                ['sku' => 'SKU1', 'name' => 'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œrÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¼n 1', 'quantity' => 1, 'price' => 10],
            ],
            'marketplace_data' => [],
        ]);

        $service = app(EInvoiceService::class);
        $first = $service->maybeCreateDraftFromOrder($order);
        $second = $service->maybeCreateDraftFromOrder($order);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('e_invoices', 1);
    }

    public function test_order_status_change_dispatches_automation_job(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'client']);
        $order = Order::create([
            'user_id' => $user->id,
            'marketplace_id' => null,
            'marketplace_order_id' => 'MP-14',
            'order_number' => 'ORD-14',
            'status' => 'pending',
            'total_amount' => 100,
            'commission_amount' => 0,
            'net_amount' => 100,
            'currency' => 'TRY',
            'customer_name' => 'Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '+905350000000',
            'shipping_address' => 'Ship',
            'billing_address' => 'Bill',
            'order_date' => now(),
            'items' => [],
            'marketplace_data' => [],
        ]);

        $order->update(['status' => 'approved']);

        Queue::assertPushed(ProcessEInvoiceAutomationJob::class, function (ProcessEInvoiceAutomationJob $job) use ($order) {
            return $job->orderId === $order->id;
        });
    }
}
