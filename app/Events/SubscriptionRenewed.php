<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionRenewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $subscriptionId,
        public readonly ?int $planId,
        public readonly ?string $planName,
        public readonly string $renewedAt,
        public readonly ?string $periodStart,
        public readonly ?string $periodEnd,
        public readonly ?string $amount,
        public readonly ?string $currency,
        public readonly string $occurredAt
    ) {
    }
}
