<?php

namespace App\Jobs;

use App\Services\Communication\CommunicationSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCommunicationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $marketplaceStoreId
    ) {
        $this->onQueue('integrations');
    }

    public function handle(CommunicationSyncService $service): void
    {
        $service->syncStore($this->marketplaceStoreId, ['question', 'message', 'review']);
    }
}

