<?php

namespace App\Integrations\Marketplaces\Contracts;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceListing;

interface MarketplaceConnectorInterface
{
    public function testConnection(MarketplaceAccount $account): array;

    public function pullListings(MarketplaceAccount $account): array;

    public function pullOrders(MarketplaceAccount $account, ?string $since = null): array;

    public function updateStock(MarketplaceListing $listing, int $quantity): array;
}
