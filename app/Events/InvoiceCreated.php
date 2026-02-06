<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly ?int $orderId,
        public readonly ?string $marketplace,
        public readonly string $invoiceId,
        public readonly ?string $invoiceNumber,
        public readonly ?string $invoiceUrl,
        public readonly ?string $totalAmount,
        public readonly ?string $currency,
        public readonly string $occurredAt
    ) {
    }
}
