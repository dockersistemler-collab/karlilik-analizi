<?php

namespace App\Jobs;

use App\Models\MarketplaceCredential;
use App\Services\Marketplace\Category\MarketplaceCategorySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMarketplaceCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 120, 600];

    public function __construct(public int $credentialId)
    {
        $this->onQueue('integrations');
    }

    public function handle(MarketplaceCategorySyncService $syncService): void
    {
        $credential = MarketplaceCredential::query()->with('marketplace')->find($this->credentialId);
        if (!$credential || !$credential->is_active) {
            return;
        }

        try {
            $count = $syncService->syncForCredential($credential);
            Log::info('Marketplace categories synced', [
                'user_id' => $credential->user_id,
                'marketplace_id' => $credential->marketplace_id,
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Marketplace categories sync failed', [
                'user_id' => $credential->user_id,
                'marketplace_id' => $credential->marketplace_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
