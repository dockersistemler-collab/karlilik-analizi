<?php

namespace App\Jobs;

use App\Models\MarketplaceCompetitorOffer;
use App\Models\MarketplaceOfferSnapshot;
use App\Models\Product;
use App\Models\User;
use App\Services\BuyBox\AdapterRegistry;
use App\Services\Modules\ModuleGate;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CollectBuyBoxSnapshotsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;

    public function __construct(
        public int $tenantId,
        public string $date
    ) {
        $this->onQueue('default');
    }

    public function handle(AdapterRegistry $registry, ModuleGate $moduleGate): void
    {
        $owner = User::query()
            ->where('id', $this->tenantId)
            ->orWhere(function ($q) {
                $q->where('tenant_id', $this->tenantId)->where('role', 'client');
            })
            ->orderBy('id')
            ->first();

        if (!$owner || !$moduleGate->isEnabledForUser($owner, 'buybox_engine')) {
            return;
        }

        $date = Carbon::parse($this->date)->startOfDay();
        $skus = $this->activeSkus($this->tenantId, $owner->id);
        if ($skus->isEmpty()) {
            return;
        }

        foreach (['trendyol', 'hepsiburada', 'amazon', 'n11'] as $marketplace) {
            $adapter = $registry->resolve($marketplace);

            $bulkRows = collect($adapter->fetchBulkSnapshots($this->tenantId, $date))
                ->filter(fn ($row) => is_array($row) && !empty($row['sku']));

            foreach ($bulkRows as $row) {
                $this->upsertSnapshot($this->tenantId, $marketplace, $date, $row);
            }

            $bulkSkus = $bulkRows->pluck('sku')->map(fn ($v) => (string) $v)->all();
            foreach ($skus as $sku) {
                if (in_array($sku, $bulkSkus, true)) {
                    continue;
                }

                $row = $adapter->fetchOfferSnapshot($this->tenantId, $sku, $date);
                if (!is_array($row)) {
                    continue;
                }

                $row['sku'] = $sku;
                $this->upsertSnapshot($this->tenantId, $marketplace, $date, $row);
            }
        }
    }

    /**
     * @return Collection<int,string>
     */
    private function activeSkus(int $tenantId, int $ownerUserId): Collection
    {
        $tenantUserIds = User::query()
            ->where('id', $tenantId)
            ->orWhere('tenant_id', $tenantId)
            ->pluck('id')
            ->push($ownerUserId)
            ->unique()
            ->values();

        return Product::query()
            ->whereIn('user_id', $tenantUserIds)
            ->where('is_active', true)
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->pluck('sku')
            ->map(fn ($sku) => trim((string) $sku))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * @param array<string,mixed> $row
     */
    private function upsertSnapshot(int $tenantId, string $marketplace, Carbon $date, array $row): void
    {
        $payload = [
            'tenant_id' => $tenantId,
            'marketplace' => $marketplace,
            'date' => $date->toDateString(),
            'sku' => (string) ($row['sku'] ?? ''),
            'listing_id' => $this->nullableString($row, 'listing_id'),
            'is_winning' => (bool) ($row['is_winning'] ?? false),
            'position_rank' => $this->nullableInt($row, 'position_rank'),
            'our_price' => $this->nullableFloat($row, 'our_price'),
            'competitor_best_price' => $this->nullableFloat($row, 'competitor_best_price'),
            'competitor_count' => $this->nullableInt($row, 'competitor_count'),
            'shipping_speed_score' => $this->nullableFloat($row, 'shipping_speed_score'),
            'stock_available' => $this->nullableInt($row, 'stock_available'),
            'store_score' => $this->nullableFloat($row, 'store_score'),
            'rating_score' => $this->nullableFloat($row, 'rating_score'),
            'promo_flag' => (bool) ($row['promo_flag'] ?? false),
            'meta' => is_array($row['meta'] ?? null) ? $row['meta'] : null,
            'source' => $this->nullableString($row, 'source') ?? 'api',
        ];

        $snapshot = MarketplaceOfferSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('marketplace', $marketplace)
            ->where('sku', (string) ($row['sku'] ?? ''))
            ->whereDate('date', $date->toDateString())
            ->first();

        if ($snapshot) {
            $snapshot->update($payload);
        } else {
            $snapshot = MarketplaceOfferSnapshot::query()->create($payload);
        }

        if (!is_array($row['competitors'] ?? null)) {
            return;
        }

        MarketplaceCompetitorOffer::query()->where('snapshot_id', $snapshot->id)->delete();
        foreach ($row['competitors'] as $competitor) {
            if (!is_array($competitor)) {
                continue;
            }

            MarketplaceCompetitorOffer::query()->create([
                'snapshot_id' => $snapshot->id,
                'seller_name' => $this->nullableString($competitor, 'seller_name'),
                'price' => $this->nullableFloat($competitor, 'price'),
                'shipping_speed' => $this->nullableString($competitor, 'shipping_speed'),
                'store_score' => $this->nullableFloat($competitor, 'store_score'),
                'is_featured' => (bool) ($competitor['is_featured'] ?? false),
                'meta' => is_array($competitor['meta'] ?? null) ? $competitor['meta'] : null,
            ]);
        }
    }

    /**
     * @param array<string,mixed> $row
     */
    private function nullableString(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function nullableInt(array $row, string $key): ?int
    {
        if (!array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
            return null;
        }

        return (int) $row[$key];
    }

    /**
     * @param array<string,mixed> $row
     */
    private function nullableFloat(array $row, string $key): ?float
    {
        if (!array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
            return null;
        }

        return (float) $row[$key];
    }
}
