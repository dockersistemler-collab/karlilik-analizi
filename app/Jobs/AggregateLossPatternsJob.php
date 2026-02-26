<?php

namespace App\Jobs;

use App\Domains\Settlements\Services\LossPatternAggregatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateLossPatternsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $payoutId,
        public ?string $runHash = null,
        public int $runVersion = 2
    ) {
    }

    public function handle(LossPatternAggregatorService $service): void
    {
        $service->aggregateForPayout($this->tenantId, $this->payoutId, $this->runHash, $this->runVersion);
    }
}
