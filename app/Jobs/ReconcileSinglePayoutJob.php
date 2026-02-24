<?php

namespace App\Jobs;

use App\Domains\Settlements\Services\ReconciliationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconcileSinglePayoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $payoutId,
        public ?float $tolerance = null
    ) {
    }

    public function handle(ReconciliationService $service): void
    {
        $service->reconcileOne($this->payoutId, $this->tolerance);
    }
}
