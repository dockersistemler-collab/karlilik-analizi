<?php

namespace App\Listeners;

use App\Events\ProductStockUpdated;
use App\Jobs\PushStockToMarketplacesJob;
use App\Services\Modules\ModuleGate;

class QueueStockSync
{
    public function __construct(
        private readonly ModuleGate $moduleGate
    ) {
    }

    public function handle(ProductStockUpdated $event): void
    {
        if (!$this->moduleGate->isActive('feature.inventory')) {
            return;
        }

        $tenantId = (int) $event->product->user_id;
        if ($tenantId <= 0) {
            return;
        }

        PushStockToMarketplacesJob::dispatch($tenantId, (int) $event->product->id);
    }
}
