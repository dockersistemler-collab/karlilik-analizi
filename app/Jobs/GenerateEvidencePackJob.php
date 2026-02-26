<?php

namespace App\Jobs;

use App\Domains\Settlements\Models\Dispute;
use App\Domains\Settlements\Services\EvidencePackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateEvidencePackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $disputeId,
        public ?int $actorId = null
    ) {
    }

    public function handle(EvidencePackService $service): void
    {
        $dispute = Dispute::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $this->tenantId)
            ->findOrFail($this->disputeId);

        $service->generate($dispute, $this->actorId);
    }
}
