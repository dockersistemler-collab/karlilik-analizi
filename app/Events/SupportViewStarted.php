<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportViewStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $supportAccessLogId,
        public readonly int $userId,
        public readonly int $adminId,
        public readonly string $reason,
        public readonly string $startedAt
    ) {
    }
}
