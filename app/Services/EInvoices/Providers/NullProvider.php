<?php

namespace App\Services\EInvoices\Providers;

use App\Models\EInvoice;

class NullProvider implements EInvoiceProviderInterface
{
    public function send(EInvoice $invoice): ProviderResult
    {
        return new ProviderResult(
            success: true,
            providerInvoiceId: "local:{$invoice->id}",
            providerStatus: 'SENT',
            raw: ['provider' => 'null'],
        );
    }

    public function status(EInvoice $invoice): ProviderResult
    {
        return new ProviderResult(true, $invoice->provider_invoice_id, $invoice->provider_status, ['provider' => 'null']);
    }

    public function cancel(EInvoice $invoice, ?string $reason = null): ProviderResult
    {
        return new ProviderResult(true, $invoice->provider_invoice_id, 'CANCELLED', ['provider' => 'null', 'reason' => $reason]);
    }

    public function refundOrReturn(EInvoice $invoice, ?string $reason = null): ProviderResult
    {
        return new ProviderResult(true, $invoice->provider_invoice_id, 'REFUNDED', ['provider' => 'null', 'reason' => $reason]);
    }
}

