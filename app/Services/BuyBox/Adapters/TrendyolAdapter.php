<?php

namespace App\Services\BuyBox\Adapters;

use Carbon\Carbon;

class TrendyolAdapter implements MarketplaceAdapterInterface
{
    public function fetchOfferSnapshot(int $tenantId, string $sku, Carbon $date): ?array
    {
        return null;
    }

    public function fetchBulkSnapshots(int $tenantId, Carbon $date): iterable
    {
        return [];
    }
}

