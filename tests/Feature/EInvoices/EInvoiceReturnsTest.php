<?php

namespace Tests\Feature\EInvoices;

use App\Models\EInvoice;
use App\Models\User;
use App\Services\EInvoices\EInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EInvoiceReturnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_return_invoice_sets_parent_and_negative_totals(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $sale = EInvoice::create([
            'user_id' => $user->id,
            'source_type' => 'order',
            'source_id' => 1,
            'status' => 'issued',
            'type' => 'sale',
            'currency' => 'TRY',
            'buyer_name' => 'Buyer',
            'subtotal' => 20,
            'tax_total' => 4,
            'grand_total' => 24,
        ]);

        $sale->items()->create([
            'sku' => 'SKU1',
            'name' => 'Ürün 1',
            'quantity' => 2,
            'unit_price' => 10,
            'vat_rate' => 20,
            'vat_amount' => 4,
            'discount_amount' => 0,
            'total' => 20,
        ]);

        $service = app(EInvoiceService::class);
        $return = $service->createReturnFromInvoice($sale, 'test');

        $this->assertSame('draft', $return->status);
        $this->assertSame('return', $return->type);
        $this->assertSame($sale->id, $return->parent_invoice_id);
        $this->assertLessThan(0, (float) $return->grand_total);
        $this->assertDatabaseHas('e_invoice_events', [
            'einvoice_id' => $return->id,
            'type' => 'created_return',
        ]);

        $item = $return->items()->first();
        $this->assertNotNull($item);
        $this->assertSame('-2.000', (string) $item->quantity);
    }

    public function test_duplicate_return_is_prevented(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $sale = EInvoice::create([
            'user_id' => $user->id,
            'source_type' => 'order',
            'source_id' => 11,
            'status' => 'issued',
            'type' => 'sale',
            'currency' => 'TRY',
            'buyer_name' => 'Buyer',
            'subtotal' => 10,
            'tax_total' => 2,
            'grand_total' => 12,
        ]);

        $sale->items()->create([
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
        $first = $service->createReturnFromInvoice($sale, 'x');
        $second = $service->createReturnFromInvoice($sale, 'x');

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('e_invoices', 2); // sale + return
    }

    public function test_create_credit_note_only_includes_selected_items(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $sale = EInvoice::create([
            'user_id' => $user->id,
            'source_type' => 'order',
            'source_id' => 2,
            'status' => 'issued',
            'type' => 'sale',
            'currency' => 'TRY',
            'buyer_name' => 'Buyer',
        ]);

        $item1 = $sale->items()->create([
            'sku' => 'SKU1',
            'name' => 'Ürün 1',
            'quantity' => 2,
            'unit_price' => 10,
            'vat_rate' => 20,
            'vat_amount' => 4,
            'discount_amount' => 0,
            'total' => 20,
        ]);
        $item2 = $sale->items()->create([
            'sku' => 'SKU2',
            'name' => 'Ürün 2',
            'quantity' => 1,
            'unit_price' => 50,
            'vat_rate' => 20,
            'vat_amount' => 10,
            'discount_amount' => 0,
            'total' => 50,
        ]);

        $service = app(EInvoiceService::class);
        $credit = $service->createCreditNoteFromInvoice($sale, [
            ['item_id' => $item2->id, 'qty' => 1],
        ], 'partial');

        $this->assertSame('credit_note', $credit->type);
        $this->assertSame($sale->id, $credit->parent_invoice_id);
        $this->assertDatabaseCount('e_invoice_items', 3); // 2 sale + 1 credit

        $creditItems = $credit->items()->get();
        $this->assertCount(1, $creditItems);
        $this->assertSame($item2->sku, $creditItems->first()->sku);
        $this->assertStringStartsWith('-', (string) $creditItems->first()->quantity);
        $this->assertDatabaseHas('e_invoice_events', [
            'einvoice_id' => $credit->id,
            'type' => 'created_credit_note',
        ]);
    }

    public function test_credit_note_qty_overflow_throws_422_validation_exception(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $sale = EInvoice::create([
            'user_id' => $user->id,
            'source_type' => 'order',
            'source_id' => 22,
            'status' => 'issued',
            'type' => 'sale',
            'currency' => 'TRY',
            'buyer_name' => 'Buyer',
        ]);

        $item = $sale->items()->create([
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

        try {
            $service->createCreditNoteFromInvoice($sale, [
                ['item_id' => $item->id, 'qty' => 2],
            ], 'bad');
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->status);
        }
    }

    public function test_cancel_invoice_sets_status_and_logs_event(): void
    {
        $user = User::factory()->create(['role' => 'client']);

        $sale = EInvoice::create([
            'user_id' => $user->id,
            'source_type' => 'order',
            'source_id' => 3,
            'status' => 'issued',
            'type' => 'sale',
            'currency' => 'TRY',
            'buyer_name' => 'Buyer',
        ]);

        $service = app(EInvoiceService::class);
        $service->cancelInvoice($sale, 'reason');

        $sale->refresh();
        $this->assertSame('cancelled', $sale->status);
        $this->assertDatabaseHas('e_invoice_events', [
            'einvoice_id' => $sale->id,
            'type' => 'cancelled',
        ]);
    }
}
