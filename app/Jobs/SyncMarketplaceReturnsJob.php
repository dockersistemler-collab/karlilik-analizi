<?php

namespace App\Jobs;

use App\Integrations\Marketplaces\MarketplaceAdapterResolver;
use App\Integrations\Marketplaces\Support\DateRange;
use App\Models\CoreOrderItem;
use App\Models\MarketplaceAccount;
use App\Models\RawMarketplaceEvent;
use App\Services\Profitability\CoreProfitabilityCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SyncMarketplaceReturnsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [30, 120, 600];

    public function __construct(
        public int $tenantId,
        public int $accountId,
        public string $dateFrom,
        public string $dateTo
    ) {
        $this->onQueue('integrations');
    }

    public function handle(
        MarketplaceAdapterResolver $resolver,
        CoreProfitabilityCalculator $calculator
    ): void {
        $account = MarketplaceAccount::query()
            ->where('tenant_id', $this->tenantId)
            ->find($this->accountId);

        if (!$account || $account->status !== 'active') {
            return;
        }

        $adapter = $resolver->resolve($account->marketplace);
        $range = new DateRange(
            CarbonImmutable::parse($this->dateFrom),
            CarbonImmutable::parse($this->dateTo)
        );

        foreach ($adapter->fetchReturns($account, $range) as $raw) {
            if (!$this->storeRawEventOnce($account, $raw, 'returns')) {
                continue;
            }

            $adjustment = $adapter->mapReturnToCoreAdjustments($raw);

            $item = CoreOrderItem::query()
                ->where('tenant_id', $account->tenant_id)
                ->where('marketplace', $adjustment->marketplace)
                ->where('order_item_id', $adjustment->orderItemId)
                ->first();

            if (!$item) {
                Log::warning('Return adjustment skipped: order item not found', [
                    'tenant_id' => $account->tenant_id,
                    'marketplace' => $adjustment->marketplace,
                    'order_item_id' => $adjustment->orderItemId,
                ]);
                continue;
            }

            $item->refunds = (float) ($item->refunds ?? 0) + (float) $adjustment->amount;
            $item->status = 'refunded';
            $calculator->recalculate($item)->save();
        }
    }

    private function storeRawEventOnce(
        MarketplaceAccount $account,
        array $raw,
        string $resourceType
    ): bool {
        if (!Schema::hasTable('raw_marketplace_events')) {
            return true;
        }

        $externalId = (string) ($raw['external_id'] ?? $raw['id'] ?? $raw['return_id'] ?? '');
        if ($externalId === '') {
            $externalId = md5(json_encode($raw));
        }

        $event = RawMarketplaceEvent::query()->firstOrCreate([
            'tenant_id' => $account->tenant_id,
            'marketplace' => $account->marketplace,
            'resource_type' => $resourceType,
            'external_id' => $externalId,
        ], [
            'payload' => $raw,
            'occurred_at' => $raw['occurred_at'] ?? null,
            'ingested_at' => now(),
        ]);

        return $event->wasRecentlyCreated;
    }
}
