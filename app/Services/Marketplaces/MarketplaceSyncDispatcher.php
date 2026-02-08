<?php

namespace App\Services\Marketplaces;

use App\Jobs\SyncMarketplaceFeesJob;
use App\Jobs\SyncMarketplaceOrdersJob;
use App\Jobs\SyncMarketplaceReturnsJob;
use App\Models\MarketplaceAccount;
use App\Integrations\Marketplaces\Support\DateRange;

class MarketplaceSyncDispatcher
{
    /**
     * @return array<int, MarketplaceAccount>
     */
    public function accountsFor(?int $tenantId, ?string $marketplace): array
    {
        $query = MarketplaceAccount::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($marketplace) {
            $query->where('marketplace', $marketplace);
        }

        return $query->get()->all();
    }

    public function dispatchAll(?int $tenantId, ?string $marketplace, DateRange $range): void
    {
        foreach ($this->accountsFor($tenantId, $marketplace) as $account) {
            SyncMarketplaceOrdersJob::dispatch(
                $account->tenant_id,
                $account->id,
                $range->from->toDateTimeString(),
                $range->to->toDateTimeString()
            );
            SyncMarketplaceReturnsJob::dispatch(
                $account->tenant_id,
                $account->id,
                $range->from->toDateTimeString(),
                $range->to->toDateTimeString()
            );
            SyncMarketplaceFeesJob::dispatch(
                $account->tenant_id,
                $account->id,
                $range->from->toDateTimeString(),
                $range->to->toDateTimeString()
            );
        }
    }

    public function dispatchOrdersAndReturns(?int $tenantId, ?string $marketplace, DateRange $range): void
    {
        foreach ($this->accountsFor($tenantId, $marketplace) as $account) {
            SyncMarketplaceOrdersJob::dispatch(
                $account->tenant_id,
                $account->id,
                $range->from->toDateTimeString(),
                $range->to->toDateTimeString()
            );
            SyncMarketplaceReturnsJob::dispatch(
                $account->tenant_id,
                $account->id,
                $range->from->toDateTimeString(),
                $range->to->toDateTimeString()
            );
        }
    }

    public function dispatchFees(?int $tenantId, ?string $marketplace, DateRange $range): void
    {
        foreach ($this->accountsFor($tenantId, $marketplace) as $account) {
            SyncMarketplaceFeesJob::dispatch(
                $account->tenant_id,
                $account->id,
                $range->from->toDateTimeString(),
                $range->to->toDateTimeString()
            );
        }
    }
}
