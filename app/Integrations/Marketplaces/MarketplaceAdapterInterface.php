<?php

namespace App\Integrations\Marketplaces;

use App\Integrations\Marketplaces\Support\DateRange;
use App\Integrations\Marketplaces\DTO\CoreOrderItemDTO;
use App\Integrations\Marketplaces\DTO\AdjustmentDTO;
use App\Models\MarketplaceAccount;

interface MarketplaceAdapterInterface
{
    /**
     * @return iterable<array<string, mixed>>
     */
    public function fetchOrders(MarketplaceAccount $account, DateRange $range): iterable;

    /**
     * @return iterable<array<string, mixed>>
     */
    public function fetchReturns(MarketplaceAccount $account, DateRange $range): iterable;

    /**
     * @return iterable<array<string, mixed>>
     */
    public function fetchFees(MarketplaceAccount $account, DateRange $range): iterable;

    public function mapOrderItemToCore(array $raw): CoreOrderItemDTO;

    public function mapReturnToCoreAdjustments(array $raw): AdjustmentDTO;

    public function mapFeeToCoreAdjustments(array $raw): AdjustmentDTO;
}
