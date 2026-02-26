<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayoutReconciled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $payoutId,
        public int $tenantId,
        public ?string $runHash = null,
        public int $runVersion = 2
    ) {
    }
}
