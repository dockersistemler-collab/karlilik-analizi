<?php

namespace App\Events;

class InvoiceCreationFailed
{
    public function __construct(
        public readonly int $tenantId,
        public readonly ?string $marketplace,
        public readonly ?int $invoiceId,
        public readonly string $reason
    ) {
    }
}