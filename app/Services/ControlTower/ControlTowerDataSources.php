<?php

namespace App\Services\ControlTower;

use App\Models\ActionRecommendation;
use App\Models\BuyBoxScore;
use App\Models\MarketplaceCampaign;
use App\Models\MarketplaceExternalShock;
use App\Models\MarketplaceOfferSnapshot;
use App\Models\MarketplacePriceHistory;
use App\Models\MarketplaceRiskScore;
use App\Models\OrderProfitSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ControlTowerDataSources
{
    /** @var array<string,bool> */
    private array $tableExistsCache = [];

    public function getProfitMetrics(int $tenantId, CarbonImmutable $date, int $rangeDays = 30, ?string $marketplace = null): ?array
    {
        if (!$this->hasTable('order_profit_snapshots')) {
            return null;
        }

        $to = $date->toDateString();
        $from = $date->subDays(max(1, $rangeDays) - 1)->toDateString();
        $prevTo = $date->subDays($rangeDays)->toDateString();
        $prevFrom = $date->subDays(($rangeDays * 2) - 1)->toDateString();

        $base = OrderProfitSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->when($marketplace !== null && $marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace));

        $current = (clone $base)
            ->whereBetween(DB::raw('DATE(calculated_at)'), [$from, $to])
            ->selectRaw('COALESCE(SUM(net_profit),0) as net_profit, COALESCE(AVG(net_margin),0) as avg_margin, COALESCE(SUM(gross_revenue),0) as revenue')
            ->first();

        $previous = (clone $base)
            ->whereBetween(DB::raw('DATE(calculated_at)'), [$prevFrom, $prevTo])
            ->selectRaw('COALESCE(SUM(net_profit),0) as net_profit')
            ->first();

        $trend = (clone $base)
            ->whereBetween(DB::raw('DATE(calculated_at)'), [$from, $to])
            ->selectRaw('DATE(calculated_at) as day, COALESCE(SUM(net_profit),0) as net_profit, COALESCE(SUM(gross_revenue),0) as revenue')
            ->groupBy(DB::raw('DATE(calculated_at)'))
            ->orderBy('day')
            ->get();

        $leaks = (clone $base)
            ->whereBetween(DB::raw('DATE(calculated_at)'), [$from, $to])
            ->selectRaw('
                COALESCE(SUM(campaign_amount),0) as campaign_erosion,
                COALESCE(SUM(return_risk_amount),0) as return_costs,
                COALESCE(SUM(commission_amount),0) as fee_drifts,
                COALESCE(SUM(CASE WHEN net_profit < 0 THEN ABS(net_profit) ELSE 0 END),0) as loss_total
            ')
            ->first();

        $priceWars = max(
            0.0,
            ((float) ($leaks->loss_total ?? 0.0))
            - ((float) ($leaks->campaign_erosion ?? 0.0))
            - ((float) ($leaks->return_costs ?? 0.0))
            - ((float) ($leaks->fee_drifts ?? 0.0))
        );

        return [
            'available' => true,
            'net_profit' => (float) ($current->net_profit ?? 0.0),
            'avg_margin' => (float) ($current->avg_margin ?? 0.0),
            'revenue' => (float) ($current->revenue ?? 0.0),
            'net_profit_prev' => (float) ($previous->net_profit ?? 0.0),
            'trend' => $trend->map(fn ($row) => [
                'day' => (string) $row->day,
                'net_profit' => (float) $row->net_profit,
                'revenue' => (float) $row->revenue,
            ])->values()->all(),
            'leak_breakdown' => [
                'campaign_erosion' => (float) ($leaks->campaign_erosion ?? 0.0),
                'return_costs' => (float) ($leaks->return_costs ?? 0.0),
                'price_wars' => $priceWars,
                'fee_drifts' => (float) ($leaks->fee_drifts ?? 0.0),
            ],
        ];
    }

    public function getRiskMetrics(int $tenantId, CarbonImmutable $date, int $rangeDays = 30, ?string $marketplace = null): ?array
    {
        if (!$this->hasTable('marketplace_risk_scores')) {
            return null;
        }

        $to = $date->toDateString();
        $from = $date->subDays(max(1, $rangeDays) - 1)->toDateString();
        $deltaFrom = $date->subDays(6)->toDateString();

        $base = MarketplaceRiskScore::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$from, $to])
            ->when($marketplace !== null && $marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace));

        $summary = (clone $base)->selectRaw('
            COALESCE(AVG(risk_score),0) as avg_risk_score,
            SUM(CASE WHEN status = "warning" THEN 1 ELSE 0 END) as warning_count,
            SUM(CASE WHEN status = "critical" THEN 1 ELSE 0 END) as critical_count
        ')->first();

        $latestRows = MarketplaceRiskScore::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', $to)
            ->when($marketplace !== null && $marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace))
            ->get(['marketplace', 'reasons']);

        $lateShipment = 0.0;
        $returnRate = 0.0;
        $count = 0;
        foreach ($latestRows as $row) {
            $lateShipment += (float) data_get($row->reasons, 'kpis.late_shipment_rate', 0.0);
            $returnRate += (float) data_get($row->reasons, 'kpis.return_rate', 0.0);
            $count++;
        }

        $trend = (clone $base)
            ->selectRaw('date as day, COALESCE(AVG(risk_score),0) as risk_score')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $driverCounts = $this->countRiskDrivers($base->get(['reasons']));

        return [
            'available' => true,
            'avg_risk_score' => (float) ($summary->avg_risk_score ?? 0.0),
            'warning_count' => (int) ($summary->warning_count ?? 0),
            'critical_count' => (int) ($summary->critical_count ?? 0),
            'late_shipment_rate_7d' => $count > 0 ? $lateShipment / $count : 0.0,
            'return_rate_7d' => $count > 0 ? $returnRate / $count : 0.0,
            'top_drivers' => $driverCounts,
            'trend' => $trend->map(fn ($row) => [
                'day' => (string) $row->day,
                'risk_score' => (float) $row->risk_score,
            ])->values()->all(),
            'latest_window_start' => $deltaFrom,
        ];
    }

    public function getBuyBoxMetrics(int $tenantId, CarbonImmutable $date, int $rangeDays = 30, ?string $marketplace = null): ?array
    {
        if (!$this->hasTable('buybox_scores')) {
            return null;
        }

        $to = $date->toDateString();
        $from = $date->subDays(max(1, $rangeDays) - 1)->toDateString();
        $sevenFrom = $date->subDays(6)->toDateString();
        $thirtyFrom = $date->subDays(29)->toDateString();

        $base = BuyBoxScore::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$from, $to])
            ->when($marketplace !== null && $marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace));

        $totals = (clone $base)->selectRaw('
            COUNT(*) as total_count,
            SUM(CASE WHEN status = "winning" THEN 1 ELSE 0 END) as winning_count,
            SUM(CASE WHEN status = "losing" THEN 1 ELSE 0 END) as losing_count
        ')->first();

        $byMarketplace = (clone $base)
            ->selectRaw('marketplace, COUNT(*) as total_count, SUM(CASE WHEN status = "winning" THEN 1 ELSE 0 END) as winning_count')
            ->groupBy('marketplace')
            ->orderBy('marketplace')
            ->get();

        $storeScoreDelta = [];
        if ($this->hasTable('marketplace_offer_snapshots')) {
            $storeRows = MarketplaceOfferSnapshot::query()
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$thirtyFrom, $to])
                ->when($marketplace !== null && $marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace))
                ->select('marketplace', 'date', 'store_score')
                ->get()
                ->groupBy('marketplace');

            foreach ($storeRows as $mp => $rows) {
                $rows = $rows instanceof Collection ? $rows : collect($rows);
                $last7 = $rows->where('date', '>=', $sevenFrom)->pluck('store_score')->filter()->map(fn ($v) => (float) $v)->values();
                $last30 = $rows->pluck('store_score')->filter()->map(fn ($v) => (float) $v)->values();
                $delta = $last7->isNotEmpty() && $last30->isNotEmpty()
                    ? ((float) $last7->avg()) - ((float) $last30->avg())
                    : 0.0;
                $storeScoreDelta[(string) $mp] = round($delta, 4);
            }
        }

        $trend = (clone $base)
            ->selectRaw('date as day, COUNT(*) as total_count, SUM(CASE WHEN status = "winning" THEN 1 ELSE 0 END) as winning_count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'available' => true,
            'win_rate_overall' => (int) ($totals->total_count ?? 0) > 0
                ? ((float) $totals->winning_count / (float) $totals->total_count)
                : 0.0,
            'losing_sku_count' => (int) ($totals->losing_count ?? 0),
            'win_rate_by_marketplace' => $byMarketplace->mapWithKeys(function ($row) {
                $rate = (int) $row->total_count > 0 ? ((float) $row->winning_count / (float) $row->total_count) : 0.0;
                return [(string) $row->marketplace => round($rate, 4)];
            })->all(),
            'store_score_delta_7d' => $storeScoreDelta,
            'trend' => $trend->map(fn ($row) => [
                'day' => (string) $row->day,
                'win_rate' => (int) $row->total_count > 0 ? ((float) $row->winning_count / (float) $row->total_count) : 0.0,
            ])->values()->all(),
        ];
    }

    public function getCampaignShockMetrics(int $tenantId, CarbonImmutable $date, int $rangeDays = 30, ?string $marketplace = null): ?array
    {
        $hasCampaigns = $this->hasTable('marketplace_campaigns');
        $hasShocks = $this->hasTable('marketplace_external_shocks');
        $hasPriceHistory = $this->hasTable('marketplace_price_history');
        if (!$hasCampaigns && !$hasShocks && !$hasPriceHistory) {
            return null;
        }

        $to = $date->toDateString();
        $from = $date->subDays(max(1, $rangeDays) - 1)->toDateString();

        $campaignCount = 0;
        $importCampaignCount = 0;
        if ($hasCampaigns) {
            $campaignQuery = MarketplaceCampaign::query()
                ->where('tenant_id', $tenantId)
                ->whereDate('start_date', '<=', $to)
                ->whereDate('end_date', '>=', $from)
                ->when($marketplace !== null && $marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace));

            $campaignCount = (int) (clone $campaignQuery)->count();
            $importCampaignCount = (int) (clone $campaignQuery)->where('source', 'import')->count();
        }

        $shockCount = 0;
        $algoShiftCount = 0;
        if ($hasShocks) {
            $shockQuery = MarketplaceExternalShock::query()
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$from, $to])
                ->when($marketplace !== null && $marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace));

            $shockCount = (int) (clone $shockQuery)->count();
            $algoShiftCount = (int) (clone $shockQuery)->where('shock_type', 'algo_shift')->count();
        }

        $promoDays = 0;
        if ($hasPriceHistory) {
            $promoQuery = MarketplacePriceHistory::query()
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$from, $to])
                ->when($marketplace !== null && $marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace));

            $promoDays = (int) (clone $promoQuery)->where('is_promo_day', true)->count();
        }

        return [
            'available' => true,
            'campaign_count' => $campaignCount,
            'import_campaign_count' => $importCampaignCount,
            'shock_count' => $shockCount,
            'algo_shift_count' => $algoShiftCount,
            'promo_day_count' => $promoDays,
        ];
    }

    public function getActionMetrics(int $tenantId, CarbonImmutable $date, int $rangeDays = 30, ?string $marketplace = null): ?array
    {
        if (!$this->hasTable('action_recommendations')) {
            return null;
        }

        $to = $date->toDateString();
        $from = $date->subDays(max(1, $rangeDays) - 1)->toDateString();

        $base = ActionRecommendation::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$from, $to])
            ->when($marketplace !== null && $marketplace !== '', fn ($q) => $q->where('marketplace', $marketplace));

        $summary = (clone $base)->selectRaw('
            SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_count,
            SUM(CASE WHEN status = "open" AND severity IN ("high","critical") THEN 1 ELSE 0 END) as critical_count
        ')->first();

        $tasks = (clone $base)
            ->with('impact:id,recommendation_id,delta')
            ->where('status', 'open')
            ->latest('date')
            ->latest('id')
            ->limit(10)
            ->get(['id', 'date', 'marketplace', 'sku', 'severity', 'action_type', 'title', 'status']);

        return [
            'available' => true,
            'open_count' => (int) ($summary->open_count ?? 0),
            'critical_count' => (int) ($summary->critical_count ?? 0),
            'task_queue' => $tasks->map(function (ActionRecommendation $row): array {
                return [
                    'id' => (int) $row->id,
                    'date' => (string) optional($row->date)->toDateString(),
                    'marketplace' => (string) $row->marketplace,
                    'sku' => (string) ($row->sku ?? ''),
                    'severity' => (string) $row->severity,
                    'action_type' => (string) $row->action_type,
                    'title' => (string) $row->title,
                    'status' => (string) $row->status,
                    'impact_delta' => is_array($row->impact?->delta) ? $row->impact->delta : null,
                ];
            })->values()->all(),
        ];
    }

    public function getCashflowMetrics(int $tenantId, CarbonImmutable $date, int $rangeDays = 30, ?string $marketplace = null): ?array
    {
        $profit = $this->getProfitMetrics($tenantId, $date, $rangeDays, $marketplace);
        if ($profit === null) {
            return null;
        }

        $dailySeries = collect((array) ($profit['trend'] ?? []))
            ->map(fn ($row) => (float) data_get($row, 'net_profit', 0.0))
            ->values();

        $avgDaily = $dailySeries->isNotEmpty() ? (float) $dailySeries->avg() : 0.0;
        $projection = [];
        $cursor = $date->addDay();
        for ($i = 0; $i < 30; $i++) {
            $projection[] = [
                'day' => $cursor->toDateString(),
                'net_profit' => round($avgDaily, 4),
            ];
            $cursor = $cursor->addDay();
        }

        $recent7 = $dailySeries->slice(-7)->avg() ?: 0.0;
        $prev7 = $dailySeries->slice(-14, 7)->avg() ?: 0.0;
        $trendDelta = (float) $recent7 - (float) $prev7;

        return [
            'available' => true,
            'forecast_30d' => round($avgDaily * 30, 4),
            'daily_projection' => $projection,
            'trend_delta_7d' => round($trendDelta, 4),
        ];
    }

    /**
     * @param Collection<int,mixed> $rows
     * @return array<string,int>
     */
    private function countRiskDrivers(Collection $rows): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $drivers = (array) data_get($row->reasons, 'drivers', []);
            foreach ($drivers as $driver) {
                $metric = (string) data_get($driver, 'metric', '');
                if ($metric === '') {
                    continue;
                }
                $counts[$metric] = (int) ($counts[$metric] ?? 0) + 1;
            }
        }

        arsort($counts);
        return array_slice($counts, 0, 8, true);
    }

    private function hasTable(string $table): bool
    {
        if (!array_key_exists($table, $this->tableExistsCache)) {
            $this->tableExistsCache[$table] = Schema::hasTable($table);
        }

        return $this->tableExistsCache[$table];
    }
}

