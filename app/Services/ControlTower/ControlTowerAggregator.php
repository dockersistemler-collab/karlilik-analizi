<?php

namespace App\Services\ControlTower;

use Carbon\CarbonImmutable;

class ControlTowerAggregator
{
    public function __construct(
        private readonly ControlTowerDataSources $sources
    ) {
    }

    public function aggregateDaily(int $tenantId, CarbonImmutable $date, int $rangeDays = 30, ?string $marketplace = null): array
    {
        $rangeDays = in_array($rangeDays, [7, 30], true) ? $rangeDays : 30;
        $profit = $this->sources->getProfitMetrics($tenantId, $date, $rangeDays, $marketplace);
        $risk = $this->sources->getRiskMetrics($tenantId, $date, $rangeDays, $marketplace);
        $buybox = $this->sources->getBuyBoxMetrics($tenantId, $date, $rangeDays, $marketplace);
        $campaignShock = $this->sources->getCampaignShockMetrics($tenantId, $date, $rangeDays, $marketplace);
        $action = $this->sources->getActionMetrics($tenantId, $date, $rangeDays, $marketplace);
        $cashflow = $this->sources->getCashflowMetrics($tenantId, $date, $rangeDays, $marketplace);

        $netProfit = (float) ($profit['net_profit'] ?? 0.0);
        $prevNetProfit = (float) ($profit['net_profit_prev'] ?? 0.0);
        $netProfitChangePct = $prevNetProfit == 0.0
            ? ($netProfit == 0.0 ? 0.0 : 100.0)
            : (($netProfit - $prevNetProfit) / max(1.0, abs($prevNetProfit))) * 100;

        $leaks = (array) ($profit['leak_breakdown'] ?? []);
        $costLeak = array_sum(array_map(fn ($v) => (float) $v, $leaks));

        $health = $this->accountHealthScore($profit, $risk, $buybox);
        $setupNeeded = $this->setupNeeded($profit, $risk, $buybox, $campaignShock, $action, $cashflow);

        return [
            'meta' => [
                'tenant_id' => $tenantId,
                'date' => $date->toDateString(),
                'range_days' => $rangeDays,
                'marketplace' => $marketplace,
            ],
            'sources' => [
                'profit' => ['available' => $profit !== null],
                'risk' => ['available' => $risk !== null],
                'buybox' => ['available' => $buybox !== null],
                'campaign_shock' => ['available' => $campaignShock !== null],
                'action' => ['available' => $action !== null],
                'cashflow' => ['available' => $cashflow !== null],
            ],
            'cfo' => [
                'net_profit_30d' => round($netProfit, 4),
                'net_profit_change_pct' => round($netProfitChangePct, 4),
                'avg_margin_30d' => round((float) ($profit['avg_margin'] ?? 0.0), 4),
                'revenue_30d' => round((float) ($profit['revenue'] ?? 0.0), 4),
                'cost_leak_30d' => round($costLeak, 4),
                'cashflow_30d_forecast' => round((float) ($cashflow['forecast_30d'] ?? 0.0), 4),
                'account_health_score' => $health,
                'profit_leak_breakdown' => [
                    'campaign_erosion' => round((float) ($leaks['campaign_erosion'] ?? 0.0), 4),
                    'return_costs' => round((float) ($leaks['return_costs'] ?? 0.0), 4),
                    'price_wars' => round((float) ($leaks['price_wars'] ?? 0.0), 4),
                    'fee_drifts' => round((float) ($leaks['fee_drifts'] ?? 0.0), 4),
                ],
                'cashflow_trend' => (array) ($cashflow['daily_projection'] ?? []),
                'profit_trend' => (array) ($profit['trend'] ?? []),
            ],
            'ops' => [
                'buybox_win_rate_overall' => round((float) ($buybox['win_rate_overall'] ?? 0.0), 4),
                'buybox_win_rate_per_marketplace' => (array) ($buybox['win_rate_by_marketplace'] ?? []),
                'store_score_delta_7d' => (array) ($buybox['store_score_delta_7d'] ?? []),
                'late_shipments_delta_7d' => [
                    'value' => round((float) ($risk['late_shipment_rate_7d'] ?? 0.0), 4),
                ],
                'return_rate_delta_7d' => [
                    'value' => round((float) ($risk['return_rate_7d'] ?? 0.0), 4),
                ],
                'algorithm_alert_count' => (int) ($campaignShock['algo_shift_count'] ?? 0),
                'open_recommendations_count' => (int) ($action['open_count'] ?? 0),
                'critical_count' => (int) ($action['critical_count'] ?? 0),
                'losing_sku_count' => (int) ($buybox['losing_sku_count'] ?? 0),
                'task_queue' => (array) ($action['task_queue'] ?? []),
                'buybox_trend' => (array) ($buybox['trend'] ?? []),
            ],
            'campaigns' => [
                'campaign_count' => (int) ($campaignShock['campaign_count'] ?? 0),
                'import_campaign_count' => (int) ($campaignShock['import_campaign_count'] ?? 0),
                'shock_count' => (int) ($campaignShock['shock_count'] ?? 0),
                'promo_day_count' => (int) ($campaignShock['promo_day_count'] ?? 0),
            ],
            'risk' => [
                'avg_risk_score' => round((float) ($risk['avg_risk_score'] ?? 0.0), 4),
                'warning_count' => (int) ($risk['warning_count'] ?? 0),
                'critical_count' => (int) ($risk['critical_count'] ?? 0),
                'top_drivers' => (array) ($risk['top_drivers'] ?? []),
            ],
            'widgets' => [
                'setup_needed' => $setupNeeded,
            ],
        ];
    }

    private function accountHealthScore(?array $profit, ?array $risk, ?array $buybox): float
    {
        $riskScore = (float) ($risk['avg_risk_score'] ?? 50.0);
        $buyboxWinRate = (float) ($buybox['win_rate_overall'] ?? 0.5);
        $margin = (float) ($profit['avg_margin'] ?? 0.0);
        $marginNormalized = max(0.0, min(100.0, $margin));
        $riskInverted = max(0.0, 100.0 - $riskScore);
        $buyboxNormalized = max(0.0, min(100.0, $buyboxWinRate * 100));

        $score = ($riskInverted * 0.40) + ($buyboxNormalized * 0.35) + ($marginNormalized * 0.25);
        return round(max(0.0, min(100.0, $score)), 2);
    }

    /**
     * @return array<int,array{source:string,message:string}>
     */
    private function setupNeeded(?array ...$sources): array
    {
        $labels = ['profit', 'risk', 'buybox', 'campaign/shock', 'action', 'cashflow'];
        $rows = [];
        foreach ($sources as $i => $source) {
            if ($source !== null) {
                continue;
            }
            $label = $labels[$i] ?? ('source_'.$i);
            $rows[] = [
                'source' => $label,
                'message' => strtoupper($label).' veri kaynağı bulunamadı. Kurulum gerekli.',
            ];
        }

        return $rows;
    }
}
