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
use Illuminate\Support\Facades\Schema;

class SyncMarketplaceOrdersJob implements ShouldQueue
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

        foreach ($adapter->fetchOrders($account, $range) as $raw) {
            $items = is_array($raw['items'] ?? null) ? $raw['items'] : null;
            if ($items) {
                foreach ($items as $item) {
                    $payload = is_array($item) ? array_merge($raw, $item) : $raw;
                    $this->upsertOrderItem($account, $payload, $calculator);
                }
                continue;
            }

            $this->upsertOrderItem($account, $raw, $calculator);
        }
    }

    private function upsertOrderItem(
        MarketplaceAccount $account,
        array $raw,
        CoreProfitabilityCalculator $calculator
    ): void {
        $adapter = app(MarketplaceAdapterResolver::class)->resolve($account->marketplace);
        $dto = $adapter->mapOrderItemToCore($raw);

        $this->storeRawEvent($account, $raw, 'orders', $dto->orderItemId);

        $attributes = [
            'tenant_id' => $account->tenant_id,
            'marketplace' => $dto->marketplace,
            'order_item_id' => $dto->orderItemId,
        ];

        $values = $dto->toArray();
        $values['tenant_id'] = $account->tenant_id;

        $item = CoreOrderItem::query()->updateOrCreate($attributes, $values);
        $calculator->recalculate($item)->save();
    }

    private function storeRawEvent(
        MarketplaceAccount $account,
        array $raw,
        string $resourceType,
        string $fallbackExternalId
    ): void {
        if (!Schema::hasTable('raw_marketplace_events')) {
            return;
        }

        $externalId = (string) ($raw['external_id'] ?? $raw['id'] ?? $fallbackExternalId);
        if ($externalId === '') {
            $externalId = md5(json_encode($raw));
        }

        RawMarketplaceEvent::query()->updateOrCreate([
            'tenant_id' => $account->tenant_id,
            'marketplace' => $account->marketplace,
            'resource_type' => $resourceType,
            'external_id' => $externalId,
        ], [
            'payload' => $raw,
            'occurred_at' => $raw['occurred_at'] ?? $raw['order_date'] ?? null,
            'ingested_at' => now(),
        ]);
    }
}
