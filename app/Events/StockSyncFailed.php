<?php

namespace App\Events;

class StockSyncFailed
{
    public function __construct(
        public readonly int $tenantId,
        public readonly ?string $marketplace,
        public readonly ?string $sku,
        public readonly string $reason
    ) {
    }
}