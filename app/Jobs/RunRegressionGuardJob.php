<?php

namespace App\Jobs;

use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Services\ReconcileRegressionGuardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunRegressionGuardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $payoutId,
        public ?string $runHash = null
    ) {
    }

    public function handle(ReconcileRegressionGuardService $service): void
    {
        $payout = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $this->tenantId)
            ->findOrFail($this->payoutId);

        $service->evaluateAndPersist($payout, $this->runHash);
    }
}
