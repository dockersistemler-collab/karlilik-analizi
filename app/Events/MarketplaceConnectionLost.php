<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketplaceConnectionLost
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $marketplace,
        public readonly string $storeId,
        public readonly int $userId,
        public readonly string $reason,
        public readonly string $occurredAt
    ) {
    }
}
