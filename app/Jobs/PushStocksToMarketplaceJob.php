<?php

namespace App\Jobs;

use App\Integrations\Marketplaces\ConnectorFactory;
use App\Models\MarketplaceListing;
use App\Models\User;
use App\Services\Modules\ModuleGate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PushStocksToMarketplaceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $marketplaceCode
    ) {
    }

    public function handle(ModuleGate $moduleGate, ConnectorFactory $factory): void
    {
        if (!$moduleGate->isActive('feature.inventory')) {
            return;
        }

        $marketplaceCode = strtolower(trim($this->marketplaceCode));
        if ($marketplaceCode === '') {
            return;
        }

        $tenant = User::query()->find($this->tenantId);
        if (!$tenant) {
            return;
        }

        $connectorModuleCode = 'integration.inventory.' . $marketplaceCode;
        if (!$moduleGate->isActive($connectorModuleCode)) {
            return;
        }

        $listings = MarketplaceListing::query()
            ->with(['account', 'product'])
            ->where('tenant_id', $tenant->id)
            ->where('sync_enabled', true)
            ->whereHas('account', function ($query) use ($marketplaceCode) {
                $query->whereRaw('LOWER(COALESCE(connector_key, marketplace)) = ?', [$marketplaceCode]);
            })
            ->get();

        foreach ($listings as $listing) {
            if (!$listing->product) {
                continue;
            }

            try {
                $connector = $factory->make($marketplaceCode);
                $connector->updateStock($listing, (int) $listing->product->stock_quantity);

                $listing->last_known_market_stock = (int) $listing->product->stock_quantity;
                $listing->save();
            } catch (\Throwable $e) {
                Log::warning('inventory.push_stocks.marketplace_failed', [
                    'tenant_id' => $tenant->id,
                    'marketplace' => $marketplaceCode,
                    'listing_id' => $listing->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
