<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrialEnded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly ?int $subscriptionId,
        public readonly ?int $planId,
        public readonly ?string $trialStartedAt,
        public readonly string $trialEndedAt,
        public readonly string $occurredAt
    ) {
    }
}
