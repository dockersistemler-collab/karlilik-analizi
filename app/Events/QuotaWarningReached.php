<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuotaWarningReached
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $quotaType,
        public readonly int $used,
        public readonly int $limit,
        public readonly int $percent,
        public readonly ?string $period,
        public readonly string $occurredAt
    ) {
    }
}
