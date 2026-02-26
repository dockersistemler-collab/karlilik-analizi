<?php

namespace App\Services\BuyBox\Adapters;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceListing;
use Carbon\Carbon;

class HepsiburadaAdapter implements MarketplaceAdapterInterface
{
    public function fetchOfferSnapshot(int $tenantId, string $sku, Carbon $date): ?array
    {
        $listing = MarketplaceListing::query()
            ->where('tenant_id', $tenantId)
            ->where('external_sku', $sku)
            ->first();

        if (!$listing) {
            return null;
        }

        return [
            'sku' => $sku,
            'listing_id' => $listing->external_listing_id,
            'is_winning' => null,
            'position_rank' => null,
            'our_price' => null,
            'competitor_best_price' => null,
            'competitor_count' => null,
            'shipping_speed_score' => null,
            'stock_available' => $listing->last_known_market_stock,
            'store_score' => null,
            'rating_score' => null,
            'promo_flag' => false,
            'source' => 'api',
            'meta' => [
                'adapter' => 'hepsiburada',
                'note' => 'Official buybox order endpoint mapping placeholder. Fill with real API fields when available.',
            ],
        ];
    }

    public function fetchBulkSnapshots(int $tenantId, Carbon $date): iterable
    {
        $hasActiveHbAccount = MarketplaceAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('marketplace', 'hepsiburada')
            ->where('is_active', true)
            ->exists();

        if (!$hasActiveHbAccount) {
            return [];
        }

        return [];
    }
}

