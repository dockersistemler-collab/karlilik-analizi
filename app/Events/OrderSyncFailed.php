<?php

namespace App\Events;

class OrderSyncFailed
{
    public function __construct(
        public readonly int $tenantId,
        public readonly ?string $marketplace,
        public readonly ?int $orderId,
        public readonly string $reason
    ) {
    }
}