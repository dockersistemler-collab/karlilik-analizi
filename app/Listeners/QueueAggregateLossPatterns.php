<?php

namespace App\Listeners;

use App\Events\PayoutReconciled;
use App\Jobs\AggregateLossPatternsJob;

class QueueAggregateLossPatterns
{
    public function handle(PayoutReconciled $event): void
    {
        AggregateLossPatternsJob::dispatch(
            $event->tenantId,
            $event->payoutId,
            $event->runHash,
            $event->runVersion
        );
    }
}
