<?php

namespace App\Services\EInvoices\Providers;

use App\Models\EInvoice;
use Illuminate\Support\Facades\Http;

class CustomHttpProvider implements EInvoiceProviderInterface
{
    /**
     * @param array{base_url?:string,api_key?:string} $credentials
     */
    public function __construct(private readonly array $credentials)
    {
    }

    public function send(EInvoice $invoice): ProviderResult
    {
        $baseUrl = rtrim((string) ($this->credentials['base_url'] ?? ''), '/');
        $apiKey = (string) ($this->credentials['api_key'] ?? '');

        if ($baseUrl === '' || $apiKey === '') {
            return new ProviderResult(false, null, null, ['error' => 'Missing credentials']);
        }
$payload = [
            'id' => $invoice->id,
            'invoice_no' => $invoice->invoice_no,
            'issued_at' => $invoice->issued_at?->toISOString(), 'currency' => $invoice->currency,
            'buyer' => [
                'name' => $invoice->buyer_name,
                'email' => $invoice->buyer_email,
                'phone' => $invoice->buyer_phone,
            ],
            'totals' => [
                'subtotal' => (float) $invoice->subtotal,
                'tax_total' => (float) $invoice->tax_total,
                'discount_total' => (float) $invoice->discount_total,
                'grand_total' => (float) $invoice->grand_total,
            ],
            'items' => $invoice->items->map(fn ($i) => [
                'sku' => $i->sku,
                'name' => $i->name,
                'quantity' => (float) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'vat_rate' => (float) $i->vat_rate,
                'vat_amount' => (float) $i->vat_amount,
                'total' => (float) $i->total,
            ])->values()->all(),
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Accept' => 'application/json',
        ])->post("{$baseUrl}/einvoices", $payload);

        if (!$response->successful()) {
            return new ProviderResult(false, null, null, [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        }
$body = $response->json();

        return new ProviderResult(
            success: true,
            providerInvoiceId: is_string($body['provider_invoice_id'] ?? null) ? $body['provider_invoice_id'] : null,
            providerStatus: is_string($body['provider_status'] ?? null) ? $body['provider_status'] : 'SENT',
            raw: is_array($body) ? $body : null,
        );
    }

    public function status(EInvoice $invoice): ProviderResult
    {
        return new ProviderResult(true, $invoice->provider_invoice_id, $invoice->provider_status, ['stub' => true]);
    }

    public function cancel(EInvoice $invoice, ?string $reason = null): ProviderResult
    {
        return new ProviderResult(true, $invoice->provider_invoice_id, 'CANCELLED', ['stub' => true, 'reason' => $reason]);
    }

    public function refundOrReturn(EInvoice $invoice, ?string $reason = null): ProviderResult
    {
        return new ProviderResult(true, $invoice->provider_invoice_id, 'REFUNDED', ['stub' => true, 'reason' => $reason]);
    }
}

