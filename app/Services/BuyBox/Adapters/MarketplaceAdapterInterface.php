<?php

namespace App\Services\BuyBox\Adapters;

use Carbon\Carbon;

interface MarketplaceAdapterInterface
{
    /**
     * @return array<string,mixed>|null
     */
    public function fetchOfferSnapshot(int $tenantId, string $sku, Carbon $date): ?array;

    /**
     * @return iterable<array<string,mixed>>
     */
    public function fetchBulkSnapshots(int $tenantId, Carbon $date): iterable;
}

