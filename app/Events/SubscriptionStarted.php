<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $subscriptionId,
        public readonly ?int $planId,
        public readonly ?string $planName,
        public readonly string $startedAt,
        public readonly ?string $endsAt,
        public readonly string $occurredAt
    ) {
    }
}
