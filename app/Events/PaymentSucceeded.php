<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSucceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly ?int $subscriptionId,
        public readonly ?int $invoiceId,
        public readonly ?string $amount,
        public readonly ?string $currency,
        public readonly string $provider,
        public readonly ?string $transactionId,
        public readonly string $occurredAt
    ) {
    }
}
