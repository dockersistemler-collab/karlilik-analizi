<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly ?int $orderId,
        public readonly ?string $marketplace,
        public readonly ?string $invoiceId,
        public readonly ?string $errorCode,
        public readonly string $errorMessage,
        public readonly string $occurredAt
    ) {
    }
}
