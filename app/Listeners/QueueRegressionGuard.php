<?php

namespace App\Listeners;

use App\Events\PayoutReconciled;
use App\Jobs\RunRegressionGuardJob;

class QueueRegressionGuard
{
    public function handle(PayoutReconciled $event): void
    {
        RunRegressionGuardJob::dispatch(
            $event->tenantId,
            $event->payoutId,
            $event->runHash
        );
    }
}
