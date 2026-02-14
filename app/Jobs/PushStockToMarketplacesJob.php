<?php

namespace App\Jobs;

use App\Integrations\Marketplaces\ConnectorFactory;
use App\Models\MarketplaceListing;
use App\Models\Product;
use App\Models\User;
use App\Services\Modules\ModuleGate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PushStockToMarketplacesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId
    ) {
    }

    public function handle(ModuleGate $moduleGate, ConnectorFactory $factory): void
    {
        if (!$moduleGate->isActive('feature.inventory')) {
            return;
        }

        $tenant = User::query()->find($this->tenantId);
        if (!$tenant) {
            return;
        }

        $product = Product::query()
            ->where('id', $this->productId)
            ->where('user_id', $tenant->id)
            ->first();
        if (!$product) {
            return;
        }

        $listings = MarketplaceListing::query()
            ->with('account')
            ->where('tenant_id', $tenant->id)
            ->where('product_id', $product->id)
            ->where('sync_enabled', true)
            ->get();

        foreach ($listings as $listing) {
            $connectorKey = strtolower((string) ($listing->account?->connector_key ?: $listing->account?->marketplace));
            if ($connectorKey === '') {
                continue;
            }

            $connectorModuleCode = 'integration.inventory.' . $connectorKey;
            if (!$moduleGate->isActive($connectorModuleCode)) {
                continue;
            }

            try {
                $connector = $factory->make($connectorKey);
                $connector->updateStock($listing, (int) $product->stock_quantity);
            } catch (\Throwable $e) {
                Log::warning('inventory.push_stock.failed', [
                    'tenant_id' => $tenant->id,
                    'product_id' => $product->id,
                    'listing_id' => $listing->id,
                    'connector' => $connectorKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
