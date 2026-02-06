<?php

namespace App\Services\EInvoices\Providers;

use App\Models\EInvoice;

interface EInvoiceProviderInterface
{
    public function send(EInvoice $invoice): ProviderResult;

    public function status(EInvoice $invoice): ProviderResult;

    public function cancel(EInvoice $invoice, ?string $reason = null): ProviderResult;

    public function refundOrReturn(EInvoice $invoice, ?string $reason = null): ProviderResult;
}

