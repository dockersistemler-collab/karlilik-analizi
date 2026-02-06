<?php

namespace App\Services\EInvoices;

use App\Models\EInvoice;
use App\Models\EInvoiceEvent;
use App\Models\EInvoiceSetting;
use App\Models\Order;
use App\Events\InvoiceFailed;
use App\Events\InvoiceCreated;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\Webhooks\WebhookService;

class EInvoiceService
{
    public function __construct(
        private readonly EInvoiceNumberingService $numbering,
        private readonly EInvoiceProviderManager $providers,
        private readonly WebhookService $webhooks,
    ) {
    }

    public function maybeCreateDraftFromOrder(Order $order): ?EInvoice
    {
        $setting = $this->getSetting($order->user_id);
        if (!$setting || !$setting->auto_draft_enabled) {
            return null;
        }

        if ((string) $order->status !== (string) $setting->draft_on_status) {
            return null;
        }

        return $this->createDraftFromOrder($order);
    }

    public function maybeIssueFromOrder(Order $order): ?EInvoice
    {
        $setting = $this->getSetting($order->user_id);
        if (!$setting || !$setting->auto_issue_enabled) {
            return null;
        }

        if ((string) $order->status !== (string) $setting->issue_on_status) {
            return null;
        }
$invoice = $this->createDraftFromOrder($order);
        return $this->issue($invoice);
    }

    public function createDraftFromOrder(Order $order): EInvoice
    {
        $invoice = DB::transaction(function () use ($order) {
            $existing = EInvoice::query()
                ->where('source_type', 'order')
                ->where('source_id', $order->id)
                ->where('user_id', $order->user_id)
                ->whereIn('status', ['draft', 'issued'])
                ->first();

            if ($existing) {
                return $existing->loadMissing(['items', 'events']);
            }
$order->loadMissing('marketplace');

            $currency = is_string($order->currency) && trim($order->currency) !== '' ? strtoupper(trim($order->currency)) : 'TRY';
            $setting = $this->getSetting($order->user_id);

            $invoice = EInvoice::create([
                'user_id' => $order->user_id,
                'source_type' => 'order',
                'source_id' => $order->id,
                'marketplace' => $order->marketplace?->code ?? $order->marketplace?->name, 'marketplace_order_no' => $order->marketplace_order_id ?? $order->order_number,
                'status' => 'draft',
                'type' => 'sale',
                'currency' => $currency,
                'buyer_name' => $order->customer_name,
                'buyer_email' => $order->customer_email,
                'buyer_phone' => $order->customer_phone,
                'billing_address_json' => $this->normalizeAddress($order->billing_address),
                'shipping_address_json' => $this->normalizeAddress($order->shipping_address),
            ]);

            [$subtotal, $taxTotal, $grandTotal] = $this->syncItemsFromOrder($invoice,
                $order,
                $setting?->default_vat_rate !== null ? (float) $setting->default_vat_rate : null
            );

            $invoice->subtotal = $subtotal;
            $invoice->tax_total = $taxTotal;
            $invoice->discount_total = 0;
            $invoice->grand_total = is_numeric($order->total_amount) ? (float) $order->total_amount : $grandTotal;
            $invoice->save();

            $invoice->events()->create(['type' => 'created_draft',
                'payload' => [
                    'source_type' => 'order',
                    'source_id' => $order->id,
                ],
            ]);

            return $invoice->load(['items', 'events']);
        });

        $invoice->loadMissing('user');
        if ($invoice->user && $invoice->wasRecentlyCreated) {
            $this->webhooks->dispatchEvent($invoice->user, 'einvoice.created', $this->buildWebhookPayload($invoice));
        }

        return $invoice;
    }

    public function issue(EInvoice $invoice): EInvoice
    {
        $didIssue = false;

        try {
            $issued = DB::transaction(function () use ($invoice, &$didIssue) {
                $invoice->refresh();
                $invoice->loadMissing(['user', 'items', 'events']);

                if ($invoice->status !== 'draft') {
                    return $invoice;
                }
$setting = $this->getSetting($invoice->user_id);
                $prefix = (string) ($setting?->prefix ?: config('einvoices.number_prefix', 'EA'));
                $now = Carbon::now();

                $invoiceNo = $this->numbering->nextNumber($invoice->user, $now, $prefix);

                $invoice->invoice_no = $invoiceNo;
                $invoice->issued_at = $now;
                $invoice->status = 'issued';

                $year = (int) $now->format('Y');
                $path = "einvoices/{$invoice->user_id}/{$year}/{$invoiceNo}.pdf";

                $pdfBinary = Pdf::loadView('admin.einvoices.pdf', ['invoice' => $invoice])->output();
                Storage::disk('local')->put($path, $pdfBinary);

                $invoice->pdf_path = $path;
                $invoice->save();
                $didIssue = true;

                $invoice->events()->create(['type' => 'issued',
                    'payload' => [
                        'invoice_no' => $invoiceNo,
                        'pdf_path' => $path,
                    ],
                ]);

                if ($invoice->user?->einvoiceSetting?->active_provider_key) {
                    // MVP: keep hook, but do not auto-send here.
                }

                return $invoice;
            });
        } catch (\Throwable $e) {
            event(new InvoiceFailed(
                $invoice->user_id,
                $invoice->source_type === 'order' ? (int) $invoice->source_id : null,
                $invoice->marketplace,
                (string) $invoice->id,
                null,
                $e->getMessage(),
                now()->toDateTimeString()
            ));
            throw $e;
        }
$issued->loadMissing('user');
        if ($issued->user && $didIssue && $issued->status === 'issued') {
            event(new InvoiceCreated(
                $issued->user_id,
                $issued->source_type === 'order' ? (int) $issued->source_id : null,
                $issued->marketplace,
                (string) $issued->id,
                $issued->invoice_no,
                route('portal.einvoices.show', $issued),
                $issued->grand_total !== null ? (string) $issued->grand_total : null,
                $issued->currency,
                now()->toDateTimeString()
            ));
            $this->webhooks->dispatchEvent($issued->user, 'einvoice.issued', $this->buildWebhookPayload($issued));
        }

        return $issued;
    }

    public function createReturnFromInvoice(EInvoice $invoice, ?string $reason = null): EInvoice
    {
        $return = DB::transaction(function () use ($invoice, $reason) {
            $invoice->refresh();
            $invoice->loadMissing(['items', 'user', 'events']);

            if ($invoice->type !== 'sale') {
                throw ValidationException::withMessages([
                    'einvoice' => 'İade faturası sadece satış faturası üzerinden oluşturulabilir.',
                ]);
            }
$existing = EInvoice::query()
                ->where('parent_invoice_id', $invoice->id)
                ->where('type', 'return')
                ->whereIn('status', ['draft', 'issued'])
                ->first();
            if ($existing) {
                return $existing->loadMissing(['items', 'events']);
            }
$return = EInvoice::create([
                'user_id' => $invoice->user_id,
                'source_type' => $invoice->source_type,
                'source_id' => $invoice->source_id,
                'parent_invoice_id' => $invoice->id,
                'marketplace' => $invoice->marketplace,
                'marketplace_order_no' => $invoice->marketplace_order_no,
                'status' => 'draft',
                'type' => 'return',
                'currency' => $invoice->currency,
                'buyer_name' => $invoice->buyer_name,
                'buyer_email' => $invoice->buyer_email,
                'buyer_phone' => $invoice->buyer_phone,
                'billing_address_json' => $invoice->billing_address_json,
                'shipping_address_json' => $invoice->shipping_address_json,
                'discount_total' => 0,
            ]);

            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach ($invoice->items as $item) {
                $qty = (float) $item->quantity;
                $negQty = $qty > 0 ? -1 * $qty : $qty;

                $lineTotal = round($negQty * (float) $item->unit_price, 2);
                $vatAmount = round($lineTotal * (float) $item->vat_rate / 100, 2);

                $subtotal += $lineTotal;
                $taxTotal += $vatAmount;

                $return->items()->create(['sku' => $item->sku,
                    'name' => $item->name,
                    'quantity' => $negQty,
                    'unit_price' => $item->unit_price,
                    'vat_rate' => $item->vat_rate,
                    'vat_amount' => $vatAmount,
                    'discount_amount' => 0,
                    'total' => $lineTotal,
                    'meta' => [
                        'source_item_id' => $item->id,
                        'reason' => $reason,
                    ],
                ]);
            }
$return->subtotal = round($subtotal, 2);
            $return->tax_total = round($taxTotal, 2);
            $return->grand_total = round($subtotal + $taxTotal, 2);
            $return->save();

            $return->events()->create(['type' => 'created_return',
                'payload' => [
                    'parent_invoice_id' => $invoice->id,
                    'reason' => $reason,
                ],
            ]);

            return $return->load(['items', 'events']);
        });

        $return->loadMissing('user');
        if ($return->user && $return->wasRecentlyCreated) {
            $this->webhooks->dispatchEvent($return->user, 'einvoice.return_created', $this->buildWebhookPayload($return));
        }

        return $return;
    }

    /**
     * @param array<int,array{item_id:int,qty:numeric,unit_price?:numeric}> $itemsToRefund
     */
    public function createCreditNoteFromInvoice(EInvoice $invoice, array $itemsToRefund, ?string $reason = null): EInvoice
    {
        $credit = DB::transaction(function () use ($invoice, $itemsToRefund, $reason) {
            $invoice->refresh();
            $invoice->loadMissing(['items']);

            if ($invoice->type !== 'sale') {
                throw ValidationException::withMessages([
                    'einvoice' => 'Kısmi iade (credit note) sadece satış faturası üzerinden oluşturulabilir.',
                ]);
            }
$credit = EInvoice::create([
                'user_id' => $invoice->user_id,
                'source_type' => $invoice->source_type,
                'source_id' => $invoice->source_id,
                'parent_invoice_id' => $invoice->id,
                'marketplace' => $invoice->marketplace,
                'marketplace_order_no' => $invoice->marketplace_order_no,
                'status' => 'draft',
                'type' => 'credit_note',
                'currency' => $invoice->currency,
                'buyer_name' => $invoice->buyer_name,
                'buyer_email' => $invoice->buyer_email,
                'buyer_phone' => $invoice->buyer_phone,
                'billing_address_json' => $invoice->billing_address_json,
                'shipping_address_json' => $invoice->shipping_address_json,
                'discount_total' => 0,
            ]);

            $byId = $invoice->items->keyBy('id');

            $subtotal = 0.0;
            $taxTotal = 0.0;

            $created = 0;

            foreach ($itemsToRefund as $row) {
                $sourceItem = $byId->get((int) $row['item_id']);
                if (!$sourceItem) {
                    throw ValidationException::withMessages([
                        'items' => 'Seçilen kalem(ler) bu faturaya ait değil.',
                    ]);
                }
$qty = is_numeric($row['qty'] ?? null) ? (float) $row['qty'] : 0.0;
                if ($qty <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'İade miktarı 0\'dan büyük olmalı.',
                    ]);
                }
$maxQty = abs((float) $sourceItem->quantity);
                if ($qty > $maxQty) {
                    throw ValidationException::withMessages([
                        'items' => 'İade miktarı, fatura kalem miktarını aşamaz.',
                    ]);
                }
$negQty = -1 * $qty;
                $unitPrice = is_numeric($row['unit_price'] ?? null) ? (float) $row['unit_price'] : (float) $sourceItem->unit_price;

                $lineTotal = round($negQty * $unitPrice, 2);
                $vatAmount = round($lineTotal * (float) $sourceItem->vat_rate / 100, 2);

                $subtotal += $lineTotal;
                $taxTotal += $vatAmount;

                $credit->items()->create(['sku' => $sourceItem->sku,
                    'name' => $sourceItem->name,
                    'quantity' => $negQty,
                    'unit_price' => $unitPrice,
                    'vat_rate' => $sourceItem->vat_rate,
                    'vat_amount' => $vatAmount,
                    'discount_amount' => 0,
                    'total' => $lineTotal,
                    'meta' => [
                        'source_item_id' => $sourceItem->id,
                        'reason' => $reason,
                    ],
                ]);

                $created++;
            }

            if ($created === 0) {
                throw ValidationException::withMessages([
                    'items' => 'En az bir kalem seçmelisiniz.',
                ]);
            }
$credit->subtotal = round($subtotal, 2);
            $credit->tax_total = round($taxTotal, 2);
            $credit->grand_total = round($subtotal + $taxTotal, 2);
            $credit->save();

            $credit->events()->create(['type' => 'created_credit_note',
                'payload' => [
                    'parent_invoice_id' => $invoice->id,
                    'reason' => $reason,
                ],
            ]);

            return $credit->load(['items', 'events']);
        });

        $credit->loadMissing('user');
        if ($credit->user && $credit->wasRecentlyCreated) {
            $this->webhooks->dispatchEvent($credit->user, 'einvoice.credit_note_created', $this->buildWebhookPayload($credit));
        }

        return $credit;
    }

    public function cancelInvoice(EInvoice $invoice, string $reason): void
    {
        $changed = DB::transaction(function () use ($invoice, $reason) {
            $invoice->refresh();

            if ($invoice->status === 'cancelled') {
                return false;
            }

            if (!in_array($invoice->status, ['issued', 'draft'], true)) {
                return false;
            }
$invoice->status = 'cancelled';
            $invoice->save();

            $invoice->events()->create(['type' => 'cancelled',
                'payload' => [
                    'reason' => $reason,
                ],
            ]);

            return true;
        });

        if ($changed) {
            $invoice->loadMissing('user');
            if ($invoice->user) {
                $this->webhooks->dispatchEvent($invoice->user, 'einvoice.cancelled', $this->buildWebhookPayload($invoice));
            }
        }
    }

    public function sendToProvider(EInvoice $invoice): EInvoice
    {
        $sent = DB::transaction(function () use ($invoice) {
            $invoice->refresh();
            $invoice->loadMissing(['user', 'items']);

            if ($invoice->status !== 'issued') {
                throw ValidationException::withMessages([
                    'status' => 'Sadece düzenlenmiş (issued) faturalar gönderilebilir.',
                ]);
            }
$provider = $this->providers->forUser($invoice->user);
            $result = $provider->send($invoice);

            if (!$result->success) {
                throw ValidationException::withMessages([
                    'provider' => 'Provider gönderimi başarısız.',
                ]);
            }
$key = (string) ($invoice->user->einvoiceSetting?->active_provider_key ?: 'null');

            $invoice->provider = $key;
            $invoice->provider_invoice_id = $result->providerInvoiceId;
            $invoice->provider_status = $result->providerStatus;
            $invoice->provider_payload_json = $result->raw;
            $invoice->status = 'sent';
            $invoice->save();

            $invoice->events()->create(['type' => 'provider_sent',
                'payload' => [
                    'provider' => $key,
                    'provider_invoice_id' => $result->providerInvoiceId,
                    'provider_status' => $result->providerStatus,
                ],
            ]);

            return $invoice;
        });

        $sent->loadMissing('user');
        if ($sent->user && $sent->status === 'sent') {
            $this->webhooks->dispatchEvent($sent->user, 'einvoice.sent', $this->buildWebhookPayload($sent));
        }

        return $sent;
    }

    /**
     * @return array<string,mixed>
     */
    private function buildWebhookPayload(EInvoice $invoice): array
    {
        return [
            'einvoice' => [
                'id' => $invoice->id,
                'status' => $invoice->status,
                'type' => $invoice->type,
                'invoice_no' => $invoice->invoice_no,
                'issued_at' => $invoice->issued_at?->toISOString(), 'totals' => [
                    'subtotal' => (float) $invoice->subtotal,
                    'tax_total' => (float) $invoice->tax_total,
                    'discount_total' => (float) $invoice->discount_total,
                    'grand_total' => (float) $invoice->grand_total,
                    'currency' => $invoice->currency,
                ],
                'marketplace' => $invoice->marketplace,
                'order_no' => $invoice->marketplace_order_no,
                'provider' => $invoice->provider,
                'provider_status' => $invoice->provider_status,
            ],
            'user' => [
                'id' => $invoice->user_id,
            ],
        ];
    }

    /**
     * @return array{0:float,1:float,2:float} subtotal,tax_total,grand_total
     */
    private function syncItemsFromOrder(EInvoice $invoice, Order $order, ?float $defaultVatRate = null): array
    {
        $items = is_array($order->items) ? $order->items : [];
        $items = Arr::isList($items) ? $items : array_values($items);

        $defaultVat = $defaultVatRate ?? (float) config('einvoices.default_vat_rate', 20);

        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($items as $raw) {
            if (!is_array($raw)) {
                continue;
            }
$sku = $this->stringOrNull(data_get($raw, 'sku')) ?? $this->stringOrNull(data_get($raw, 'barcode')) ?? $this->stringOrNull(data_get($raw, 'merchantSku'));

            $name = $this->stringOrNull(data_get($raw, 'name')) ?? $this->stringOrNull(data_get($raw, 'title')) ?? 'Ürün';

            $qty = data_get($raw, 'quantity', data_get($raw, 'qty', data_get($raw, 'amount', 1)));
            $qty = is_numeric($qty) ? (float) $qty : 1.0;
            $qty = $qty > 0 ? $qty : 1.0;

            $unit = data_get($raw, 'price', data_get($raw, 'unit_price', data_get($raw, 'salePrice', 0)));
            $unit = is_numeric($unit) ? (float) $unit : 0.0;
            $unit = max($unit, 0.0);

            $vatRate = data_get($raw, 'vat_rate', data_get($raw, 'vatRate', $defaultVat));
            $vatRate = is_numeric($vatRate) ? (float) $vatRate : $defaultVat;
            $vatRate = max($vatRate, 0.0);

            $lineTotal = round($qty * $unit, 2);
            $vatAmount = round($lineTotal * $vatRate / 100, 2);

            $subtotal += $lineTotal;
            $taxTotal += $vatAmount;

            $invoice->items()->create(['sku' => $sku,
                'name' => $name,
                'quantity' => $qty,
                'unit_price' => $unit,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'discount_amount' => 0,
                'total' => $lineTotal,
                'meta' => [
                    'source' => 'order_items',
                    'raw' => $this->limitArrayDepth($raw),
                ],
            ]);
        }
$grandTotal = round($subtotal + $taxTotal, 2);

        return [round($subtotal, 2), round($taxTotal, 2), $grandTotal];
    }

    private function getSetting(int $userId): ?EInvoiceSetting
    {
        return EInvoiceSetting::query()->where('user_id', $userId)->first();
    }

    private function normalizeAddress(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = trim($value);
            return $value !== '' ? ['text' => $value] : null;
        }

        return null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
$value = trim($value);
        return $value !== '' ? $value : null;
    }

    /**
     * @param array<string,mixed> $value
     * @return array<string,mixed>
     */
    private function limitArrayDepth(array $value): array
    {
        return Arr::map($value, function ($v) {
            if (is_array($v)) {
                return Arr::map($v, fn ($vv) => is_array($vv) ? '[array]' : $vv);
            }
            return $v;
        });
    }
}


