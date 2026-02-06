<?php

namespace App\Events;

class MarketplaceTokenExpired
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $marketplace,
        public readonly string $reason
    ) {
    }
}