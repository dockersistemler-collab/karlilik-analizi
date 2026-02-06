<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketplaceTokenExpiring
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $marketplaceCredentialId,
        public readonly string $marketplace,
        public readonly string $expiresAt,
        public readonly int $daysLeft,
        public readonly string $occurredAt
    ) {
    }
}
