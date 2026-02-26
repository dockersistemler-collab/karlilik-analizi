<?php

namespace App\Services\ActionEngine;

use App\Domains\Settlements\Models\OrderItem;
use App\Models\ActionEngineRun;
use App\Models\BuyBoxScore;
use App\Models\MarketplaceRiskScore;
use App\Models\Order;
use App\Models\OrderProfitSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ActionEngine
{
    private const BUYBOX_MIN_MARGIN = 5.0;

    public function __construct(
        private readonly RecommendationWriter $writer,
        private readonly NotificationPublisher $publisher,
        private readonly ImpactSimulator $impactSimulator
    ) {
    }

    public function runForDate(int $tenantId, int $userId, CarbonImmutable $date): array
    {
        $scores = MarketplaceRiskScore::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereDate('date', $date->toDateString())
            ->get();

        $stats = [
            'scanned_scores' => $scores->count(),
            'generated' => 0,
            'updated' => 0,
            'skipped' => 0,
            'by_type' => [],
        ];

        foreach ($scores as $score) {
            $marketplace = strtolower((string) $score->marketplace);
            $profit = $this->profitSummary($tenantId, $userId, $date, $marketplace);
            $drivers = collect((array) data_get($score->reasons, 'drivers', []))
                ->pluck('metric')
                ->filter()
                ->map(fn ($m) => (string) $m)
                ->values()
                ->all();

            $recommendations = $this->buildRecommendations(
                $date,
                $marketplace,
                (float) $score->risk_score,
                (string) $score->status,
                $drivers,
                $profit
            );

            foreach ($recommendations as $recommendation) {
                $result = $this->writer->write($tenantId, $userId, $recommendation);
                if ($result['created']) {
                    $stats['generated']++;
                    $stats['by_type'][$recommendation['action_type']] = (int) ($stats['by_type'][$recommendation['action_type']] ?? 0) + 1;
                    $this->publisher->publishNewRecommendation($result['model']);
                    $this->impactSimulator->simulateAndStore($result['model']);
                } elseif ($result['updated']) {
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            }
        }

        $buyBoxStats = $this->runBuyBoxRulesForDate($tenantId, $userId, $date);
        $stats['scanned_scores'] += (int) ($buyBoxStats['scanned_scores'] ?? 0);
        $stats['generated'] += (int) ($buyBoxStats['generated'] ?? 0);
        $stats['updated'] += (int) ($buyBoxStats['updated'] ?? 0);
        $stats['skipped'] += (int) ($buyBoxStats['skipped'] ?? 0);
        foreach ((array) ($buyBoxStats['by_type'] ?? []) as $type => $count) {
            $stats['by_type'][(string) $type] = (int) ($stats['by_type'][(string) $type] ?? 0) + (int) $count;
        }

        $run = ActionEngineRun::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('run_date', $date->toDateString())
            ->first();

        if ($run) {
            $run->update([
                'user_id' => $userId,
                'stats' => $stats,
            ]);
        } else {
            ActionEngineRun::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'run_date' => $date->toDateString(),
                'stats' => $stats,
            ]);
        }

        return $stats;
    }

    /**
     * @return array{scanned_scores:int,generated:int,updated:int,skipped:int,by_type:array<string,int>}
     */
    public function runBuyBoxRulesForDate(
        int $tenantId,
        int $userId,
        CarbonImmutable $date,
        ?string $marketplaceScope = null,
        ?string $skuScope = null
    ): array {
        $scores = BuyBoxScore::query()
            ->with('snapshot')
            ->where('tenant_id', $tenantId)
            ->whereDate('date', $date->toDateString())
            ->where('status', 'losing')
            ->when($marketplaceScope !== null && $marketplaceScope !== '', fn ($q) => $q->where('marketplace', strtolower(trim($marketplaceScope))))
            ->when($skuScope !== null && $skuScope !== '', fn ($q) => $q->where('sku', trim($skuScope)))
            ->get();

        $riskByMarketplace = MarketplaceRiskScore::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', $date->toDateString())
            ->get()
            ->keyBy(fn ($row) => strtolower((string) $row->marketplace));

        $stats = [
            'scanned_scores' => $scores->count(),
            'generated' => 0,
            'updated' => 0,
            'skipped' => 0,
            'by_type' => [],
        ];

        foreach ($scores as $score) {
            if (!$score->snapshot) {
                $stats['skipped']++;
                continue;
            }

            $marketplace = strtolower((string) $score->marketplace);
            $sku = trim((string) $score->sku);
            $drivers = collect((array) $score->drivers)
                ->pluck('metric')
                ->filter()
                ->map(fn ($m) => (string) $m)
                ->values()
                ->all();

            $riskScore = $riskByMarketplace->get($marketplace);
            $riskStatus = strtolower((string) ($riskScore->status ?? 'unknown'));

            $marginContext = $this->resolveSkuMarginContext($tenantId, $userId, $date, $marketplace, $sku);
            $recommendations = $this->buildBuyBoxRecommendations(
                $date,
                $marketplace,
                $sku,
                $score,
                $drivers,
                $marginContext,
                $riskStatus
            );

            foreach ($recommendations as $recommendation) {
                $result = $this->writer->write($tenantId, $userId, $recommendation);
                if ($result['created']) {
                    $stats['generated']++;
                    $stats['by_type'][$recommendation['action_type']] = (int) ($stats['by_type'][$recommendation['action_type']] ?? 0) + 1;
                    $this->publisher->publishNewRecommendation($result['model']);
                    $this->impactSimulator->simulateAndStore($result['model']);
                } elseif ($result['updated']) {
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            }
        }

        return $stats;
    }

    private function buildRecommendations(
        CarbonImmutable $date,
        string $marketplace,
        float $riskScore,
        string $status,
        array $drivers,
        array $profit
    ): array {
        $out = [];
        $topSku = (string) ($profit['top_negative_sku'] ?? '');
        $avgNetProfit = (float) ($profit['avg_net_profit'] ?? 0);
        $avgNetMargin = (float) ($profit['avg_net_margin'] ?? 0);

        if ($status === 'critical' && $avgNetProfit < 0) {
            $actionType = $riskScore >= 85 ? 'LISTING_SUSPEND' : 'PRICE_INCREASE';
            $out[] = [
                'date' => $date->toDateString(),
                'marketplace' => $marketplace,
                'sku' => $topSku,
                'severity' => 'critical',
                'action_type' => $actionType,
                'title' => $actionType === 'LISTING_SUSPEND'
                    ? 'Kritik risk ve negatif kar: listelemeyi durdur'
                    : 'Kritik risk ve negatif kar: fiyat artisi dene',
                'description' => sprintf(
                    '%s icin risk %.2f ve ortalama net kar %.2f. %s',
                    strtoupper($marketplace),
                    $riskScore,
                    $avgNetProfit,
                    $topSku !== '' ? "SKU: {$topSku}" : 'SKU belirlenemedi'
                ),
                'suggested_payload' => [
                    'risk_score' => $riskScore,
                    'avg_net_profit' => $avgNetProfit,
                    'target_price_increase_pct' => $actionType === 'PRICE_INCREASE' ? 5 : null,
                    'sku' => $topSku !== '' ? $topSku : null,
                ],
                'reason' => [
                    'status' => $status,
                    'drivers' => $drivers,
                    'profit' => $profit,
                ],
            ];
        }

        if (in_array('late_shipment_rate', $drivers, true)) {
            $out[] = [
                'date' => $date->toDateString(),
                'marketplace' => $marketplace,
                'sku' => '',
                'severity' => $status === 'critical' ? 'high' : 'medium',
                'action_type' => 'SHIPPING_SLA_FIX',
                'title' => 'Geciken kargo orani yuksek',
                'description' => strtoupper($marketplace).' icin teslimat SLA iyilestirme aksiyonu onerilir.',
                'suggested_payload' => ['focus' => 'late_shipment_rate', 'ops' => ['cutoff_time_review', 'carrier_mix_review']],
                'reason' => ['drivers' => $drivers, 'status' => $status],
            ];
        }

        if (in_array('return_rate', $drivers, true) && $avgNetMargin < 10) {
            $out[] = [
                'date' => $date->toDateString(),
                'marketplace' => $marketplace,
                'sku' => $topSku,
                'severity' => $status === 'critical' ? 'high' : 'medium',
                'action_type' => $avgNetMargin < 3 ? 'RULE_REVIEW' : 'CUSTOMER_SUPPORT',
                'title' => 'Iade kaynakli marj baskisi',
                'description' => sprintf('Iade driver aktif ve net marj %.2f. Kural/musteri sureci gozden gecirilmeli.', $avgNetMargin),
                'suggested_payload' => ['avg_net_margin' => $avgNetMargin, 'sku' => $topSku !== '' ? $topSku : null],
                'reason' => ['drivers' => $drivers, 'profit' => $profit],
            ];
        }

        if ($marketplace === 'amazon' && in_array('odr', $drivers, true)) {
            $out[] = [
                'date' => $date->toDateString(),
                'marketplace' => $marketplace,
                'sku' => '',
                'severity' => $status === 'critical' ? 'high' : 'medium',
                'action_type' => 'CUSTOMER_SUPPORT',
                'title' => 'Amazon ODR riski',
                'description' => 'ODR driver nedeniyle musteri destek eskalasyon aksiyonu onerildi.',
                'suggested_payload' => ['focus' => 'odr', 'ops' => ['proactive_message', 'refund_policy_review']],
                'reason' => ['drivers' => $drivers, 'status' => $status],
            ];
        }

        return $this->uniqueByTypeSku($out);
    }

    private function uniqueByTypeSku(array $rows): array
    {
        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            $key = ($row['action_type'] ?? '').'|'.($row['marketplace'] ?? '').'|'.($row['sku'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param array<int,string> $drivers
     * @param array{avg_net_margin:float,avg_net_profit:float,samples:int,source:string} $marginContext
     * @return array<int,array<string,mixed>>
     */
    private function buildBuyBoxRecommendations(
        CarbonImmutable $date,
        string $marketplace,
        string $sku,
        BuyBoxScore $score,
        array $drivers,
        array $marginContext,
        string $riskStatus
    ): array {
        $snapshot = $score->snapshot;
        if (!$snapshot) {
            return [];
        }

        $out = [];
        $ourPrice = $snapshot->our_price !== null ? (float) $snapshot->our_price : null;
        $bestPrice = $snapshot->competitor_best_price !== null ? (float) $snapshot->competitor_best_price : null;
        $priceGapPct = ($ourPrice !== null && $bestPrice !== null && $bestPrice > 0)
            ? (($ourPrice - $bestPrice) / $bestPrice) * 100
            : null;
        $avgNetMargin = (float) ($marginContext['avg_net_margin'] ?? 0);
        $riskCritical = $riskStatus === 'critical';

        $topDrivers = array_slice($drivers, 0, 2);
        $storeOrShippingDriver = in_array('store_score', $topDrivers, true)
            || in_array('shipping_speed', $topDrivers, true);

        if ($priceGapPct !== null && $priceGapPct > 0) {
            if ($avgNetMargin >= self::BUYBOX_MIN_MARGIN && !$riskCritical) {
                $target = $bestPrice;
                $guarded = false;
                if ($ourPrice !== null && $ourPrice > 0 && $avgNetMargin > 0) {
                    $estimatedCost = $ourPrice * (1 - ($avgNetMargin / 100));
                    $minAllowedPrice = $estimatedCost / (1 - (self::BUYBOX_MIN_MARGIN / 100));
                    if ($target !== null && $target < $minAllowedPrice) {
                        $target = round($minAllowedPrice, 4);
                        $guarded = true;
                    }
                }

                $out[] = [
                    'date' => $date->toDateString(),
                    'marketplace' => $marketplace,
                    'sku' => $sku,
                    'severity' => $priceGapPct >= 5 ? 'high' : 'medium',
                    'action_type' => 'PRICE_ADJUST',
                    'title' => 'BuyBox kaybi: fiyat ayari onerisi',
                    'description' => sprintf(
                        'Fiyat farki %s. Rakip seviyesine inerek BuyBox olasiligi artirilabilir.',
                        number_format($priceGapPct, 2)
                    ),
                    'suggested_payload' => [
                        'target_price' => $target,
                        'current_price' => $ourPrice,
                        'competitor_best_price' => $bestPrice,
                        'price_gap_pct' => round($priceGapPct, 4),
                        'min_margin' => self::BUYBOX_MIN_MARGIN,
                        'margin_guard_applied' => $guarded,
                    ],
                    'reason' => [
                        'source' => 'buybox_engine',
                        'buybox_score' => $score->buybox_score,
                        'status' => $score->status,
                        'drivers' => $drivers,
                        'risk_status' => $riskStatus,
                        'margin' => $marginContext,
                    ],
                ];
            } elseif ($riskCritical) {
                $out[] = [
                    'date' => $date->toDateString(),
                    'marketplace' => $marketplace,
                    'sku' => $sku,
                    'severity' => 'medium',
                    'action_type' => 'LISTING_OPTIMIZE',
                    'title' => 'Kritik riskte fiyat savasi bloklandi',
                    'description' => 'Risk status CRITICAL iken fiyat dusurme onerilmez. Once operasyon ve liste kalite aksiyonlari uygulanmali.',
                    'suggested_payload' => [
                        'price_adjust_blocked' => true,
                        'block_reason' => 'risk_critical',
                        'recommended_steps' => ['content_quality_review', 'fulfillment_review', 'customer_message_sla'],
                    ],
                    'reason' => [
                        'source' => 'buybox_engine',
                        'risk_status' => $riskStatus,
                        'drivers' => $drivers,
                        'price_gap_pct' => round((float) $priceGapPct, 4),
                    ],
                ];
            }
        }

        if ($storeOrShippingDriver) {
            $out[] = [
                'date' => $date->toDateString(),
                'marketplace' => $marketplace,
                'sku' => $sku,
                'severity' => $riskCritical ? 'high' : 'medium',
                'action_type' => 'SHIPPING_SLA_FIX',
                'title' => 'Store/teslimat driveri oncelikli',
                'description' => 'BuyBox kaybi store_score veya shipping_speed driveri ile iliskili. SLA odakli operasyon aksiyonu onerildi.',
                'suggested_payload' => [
                    'checklist' => [
                        'cutoff_saatini_one_cek',
                        'tasiyici_fulfillment_ayari',
                        'musteri_mesaj_sla',
                        'iptal_ve_gec_kargo_azaltma',
                    ],
                ],
                'reason' => [
                    'source' => 'buybox_engine',
                    'drivers' => $drivers,
                    'risk_status' => $riskStatus,
                ],
            ];
        }

        $stock = $snapshot->stock_available !== null ? (int) $snapshot->stock_available : null;
        if ($stock !== null && $stock <= 3) {
            $out[] = [
                'date' => $date->toDateString(),
                'marketplace' => $marketplace,
                'sku' => $sku,
                'severity' => $stock <= 0 ? 'high' : 'medium',
                'action_type' => 'STOCK_FIX',
                'title' => 'Dusuk stok BuyBox kaybini tetikliyor',
                'description' => $stock <= 0 ? 'Stok tukenmis. BuyBox geri kazanimi icin stok yenileme acil.' : 'Stok seviyesi dusuk. Stok guvencesi artirilmali.',
                'suggested_payload' => [
                    'current_stock' => $stock,
                    'recommended_min_stock' => 10,
                ],
                'reason' => [
                    'source' => 'buybox_engine',
                    'drivers' => $drivers,
                ],
            ];
        }

        return $this->uniqueByTypeSku($out);
    }

    private function profitSummary(int $tenantId, int $userId, CarbonImmutable $date, string $marketplace): array
    {
        $snapshots = OrderProfitSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('marketplace', $marketplace)
            ->whereHas('order', fn ($q) => $q->whereDate('order_date', $date->toDateString()))
            ->get(['id', 'order_id', 'net_profit', 'net_margin']);

        $avgNetProfit = (float) ($snapshots->avg('net_profit') ?? 0);
        $avgNetMargin = (float) ($snapshots->avg('net_margin') ?? 0);
        $negativeCount = (int) $snapshots->filter(fn ($s) => (float) $s->net_profit < 0)->count();

        $topNegativeSku = $this->topNegativeSku($snapshots, $marketplace, $date);

        return [
            'orders' => $snapshots->count(),
            'negative_orders' => $negativeCount,
            'avg_net_profit' => $avgNetProfit,
            'avg_net_margin' => $avgNetMargin,
            'top_negative_sku' => $topNegativeSku,
        ];
    }

    private function topNegativeSku(Collection $snapshots, string $marketplace, CarbonImmutable $date): string
    {
        $orderIds = $snapshots
            ->filter(fn ($s) => (float) $s->net_profit < 0)
            ->pluck('order_id')
            ->filter()
            ->values();

        if ($orderIds->isEmpty()) {
            return '';
        }

        $skuRow = OrderItem::query()
            ->whereIn('order_id', $orderIds)
            ->selectRaw('sku, COUNT(*) as c')
            ->groupBy('sku')
            ->orderByDesc('c')
            ->first();

        return $skuRow?->sku ? (string) $skuRow->sku : '';
    }

    /**
     * @return array{avg_net_margin:float,avg_net_profit:float,samples:int,source:string}
     */
    private function resolveSkuMarginContext(
        int $tenantId,
        int $userId,
        CarbonImmutable $date,
        string $marketplace,
        string $sku
    ): array {
        $skuQuery = OrderProfitSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('marketplace', $marketplace)
            ->whereHas('order', fn ($q) => $q->whereDate('order_date', $date->toDateString()))
            ->whereHas('order.orderItems', fn ($q) => $q->where('sku', $sku));

        $skuSamples = (int) $skuQuery->count();
        if ($skuSamples > 0) {
            return [
                'avg_net_margin' => (float) ($skuQuery->avg('net_margin') ?? 0),
                'avg_net_profit' => (float) ($skuQuery->avg('net_profit') ?? 0),
                'samples' => $skuSamples,
                'source' => 'sku',
            ];
        }

        $marketQuery = OrderProfitSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('marketplace', $marketplace)
            ->whereHas('order', fn ($q) => $q->whereDate('order_date', $date->toDateString()));

        $marketSamples = (int) $marketQuery->count();

        return [
            'avg_net_margin' => (float) ($marketQuery->avg('net_margin') ?? 0),
            'avg_net_profit' => (float) ($marketQuery->avg('net_profit') ?? 0),
            'samples' => $marketSamples,
            'source' => $marketSamples > 0 ? 'marketplace' : 'none',
        ];
    }
}
