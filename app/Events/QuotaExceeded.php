<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuotaExceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $quotaKey,
        public readonly int $limit,
        public readonly int $used,
        public readonly string $period,
        public readonly ?string $resetAt,
        public readonly string $occurredAt
    ) {
    }
}
