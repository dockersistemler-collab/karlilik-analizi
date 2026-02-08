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

class SyncMarketplaceFeesJob implements ShouldQueue
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

        foreach ($adapter->fetchFees($account, $range) as $raw) {
            if (!$this->storeRawEventOnce($account, $raw, 'fees')) {
                continue;
            }

            $adjustment = $adapter->mapFeeToCoreAdjustments($raw);

            $item = CoreOrderItem::query()
                ->where('tenant_id', $account->tenant_id)
                ->where('marketplace', $adjustment->marketplace)
                ->where('order_item_id', $adjustment->orderItemId)
                ->first();

            if (!$item) {
                Log::warning('Fee adjustment skipped: order item not found', [
                    'tenant_id' => $account->tenant_id,
                    'marketplace' => $adjustment->marketplace,
                    'order_item_id' => $adjustment->orderItemId,
                ]);
                continue;
            }

            $this->applyFeeAdjustment($item, $adjustment->type, $adjustment->amount);
            $calculator->recalculate($item)->save();
        }
    }

    private function applyFeeAdjustment(CoreOrderItem $item, string $type, float $amount): void
    {
        $normalized = strtolower(trim($type));

        if (str_contains($normalized, 'commission')) {
            $item->commission_fee = (float) ($item->commission_fee ?? 0) + $amount;
            return;
        }

        if (str_contains($normalized, 'payment')) {
            $item->payment_fee = (float) ($item->payment_fee ?? 0) + $amount;
            return;
        }

        if (str_contains($normalized, 'shipping') || str_contains($normalized, 'cargo')) {
            $item->shipping_fee = (float) ($item->shipping_fee ?? 0) + $amount;
            return;
        }

        $item->other_fees = (float) ($item->other_fees ?? 0) + $amount;
    }

    private function storeRawEventOnce(
        MarketplaceAccount $account,
        array $raw,
        string $resourceType
    ): bool {
        if (!Schema::hasTable('raw_marketplace_events')) {
            return true;
        }

        $externalId = (string) ($raw['external_id'] ?? $raw['id'] ?? $raw['fee_id'] ?? '');
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
