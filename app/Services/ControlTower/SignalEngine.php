<?php

namespace App\Services\ControlTower;

use Carbon\CarbonImmutable;

class SignalEngine
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function generateSignals(int $tenantId, CarbonImmutable $date, array $payload): array
    {
        $signals = [];

        $cfo = (array) data_get($payload, 'cfo', []);
        $ops = (array) data_get($payload, 'ops', []);
        $risk = (array) data_get($payload, 'risk', []);
        $campaigns = (array) data_get($payload, 'campaigns', []);
        $marketplace = data_get($payload, 'meta.marketplace');

        $campaignErosion = (float) data_get($cfo, 'profit_leak_breakdown.campaign_erosion', 0.0);
        $returnCosts = (float) data_get($cfo, 'profit_leak_breakdown.return_costs', 0.0);
        $priceWars = (float) data_get($cfo, 'profit_leak_breakdown.price_wars', 0.0);
        $feeDrifts = (float) data_get($cfo, 'profit_leak_breakdown.fee_drifts', 0.0);
        $netProfitChange = (float) data_get($cfo, 'net_profit_change_pct', 0.0);

        if ($campaignErosion > 0 || $returnCosts > 0 || $priceWars > 0 || $feeDrifts > 0) {
            $signals[] = $this->signal(
                $tenantId,
                $date,
                'global',
                $marketplace,
                null,
                $netProfitChange <= -15 ? 'critical' : 'warning',
                'PROFIT_LEAK',
                'Kâr Sızıntısı Tespit Edildi',
                'Son dönem kârlılıkta kayıp kalemleri yükseliyor. Leak breakdown üzerinden hızlı aksiyon önerilir.',
                [
                    'campaign_erosion' => $campaignErosion,
                    'return_costs' => $returnCosts,
                    'price_wars' => $priceWars,
                    'fee_drifts' => $feeDrifts,
                    'net_profit_change_pct' => $netProfitChange,
                ],
                [
                    'suggested_action_type' => 'RULE_REVIEW',
                    'url' => '/admin/control-tower/drill/profit-leak',
                    'payload' => ['focus' => 'profit_leak'],
                ]
            );
        }

        $buyboxWinRate = (float) data_get($ops, 'buybox_win_rate_overall', 0.0);
        $losingSkuCount = (int) data_get($ops, 'losing_sku_count', 0);
        if ($buyboxWinRate < 0.45 || $losingSkuCount >= 10) {
            $signals[] = $this->signal(
                $tenantId,
                $date,
                'global',
                $marketplace,
                null,
                $buyboxWinRate < 0.35 ? 'critical' : 'warning',
                'BUYBOX_LOSS',
                'BuyBox Kaybı Artıyor',
                'Winning rate düştü ve losing SKU sayısı arttı. Fiyat/store_score driverları kontrol edilmeli.',
                [
                    'buybox_win_rate' => $buyboxWinRate,
                    'losing_sku_count' => $losingSkuCount,
                ],
                [
                    'suggested_action_type' => 'PRICE_ADJUST',
                    'url' => '/admin/control-tower/drill/buybox',
                    'payload' => ['focus' => 'buybox_loss'],
                ]
            );
        }

        foreach ((array) data_get($ops, 'store_score_delta_7d', []) as $mp => $delta) {
            $delta = (float) $delta;
            if ($delta >= -3.0) {
                continue;
            }
            $signals[] = $this->signal(
                $tenantId,
                $date,
                'marketplace',
                (string) $mp,
                null,
                $delta <= -6.0 ? 'critical' : 'warning',
                'STORE_SCORE_DROP',
                strtoupper((string) $mp).' Store Score Düşüşü',
                '7 günlük store_score ortalaması 30 günlük ortalamanın belirgin şekilde altında.',
                [
                    'store_score_delta_7d' => $delta,
                    'threshold' => -3.0,
                ],
                [
                    'suggested_action_type' => 'SHIPPING_SLA_FIX',
                    'url' => '/admin/control-tower/drill/risk',
                    'payload' => ['marketplace' => $mp, 'focus' => 'store_score'],
                ]
            );
        }

        $lateShipmentDelta = (float) data_get($ops, 'late_shipments_delta_7d.value', 0.0);
        if ($lateShipmentDelta >= 0.08) {
            $signals[] = $this->signal(
                $tenantId,
                $date,
                'global',
                $marketplace,
                null,
                $lateShipmentDelta >= 0.12 ? 'critical' : 'warning',
                'SHIPPING_SLA',
                'Kargo SLA Riski',
                'Late shipment oranı yüksek. Operasyonel SLA düzeltme aksiyonu gerekli.',
                [
                    'late_shipment_rate' => $lateShipmentDelta,
                    'threshold' => 0.08,
                ],
                [
                    'suggested_action_type' => 'SHIPPING_SLA_FIX',
                    'url' => '/admin/control-tower/drill/risk',
                    'payload' => ['focus' => 'late_shipment_rate'],
                ]
            );
        }

        if ($campaignErosion > 0 && (int) ($campaigns['campaign_count'] ?? 0) > 0) {
            $importBoost = (int) ($campaigns['import_campaign_count'] ?? 0) > 0;
            $signals[] = $this->signal(
                $tenantId,
                $date,
                'global',
                $marketplace,
                null,
                $campaignErosion > 5000 ? 'critical' : 'warning',
                'CAMPAIGN_EROSION',
                'Kampanya Erozyonu',
                $importBoost
                    ? 'Import kampanya günlerinde marj erozyonu gözlendi. Güven seviyesi yüksek.'
                    : 'Kampanya dönemlerinde marj düşüşü gözlendi.',
                [
                    'campaign_erosion' => $campaignErosion,
                    'campaign_count' => (int) ($campaigns['campaign_count'] ?? 0),
                    'import_campaign_count' => (int) ($campaigns['import_campaign_count'] ?? 0),
                    'confidence' => $importBoost ? 'high' : 'medium',
                ],
                [
                    'suggested_action_type' => 'RULE_REVIEW',
                    'url' => '/admin/control-tower/drill/campaigns',
                    'payload' => ['focus' => 'campaign_erosion'],
                ]
            );
        }

        if ($feeDrifts > 0 || ((float) data_get($cfo, 'avg_margin_30d', 0.0) < 8 && $netProfitChange < 0)) {
            $signals[] = $this->signal(
                $tenantId,
                $date,
                'global',
                $marketplace,
                null,
                $feeDrifts > 3000 ? 'critical' : 'warning',
                'FEE_DRIFT',
                'Komisyon/Maliyet Drifti',
                'Fee ve marj kırılımında bozulma var. Kurallar ve komisyon tarifeleri gözden geçirilmeli.',
                [
                    'fee_drifts' => $feeDrifts,
                    'avg_margin_30d' => (float) data_get($cfo, 'avg_margin_30d', 0.0),
                    'net_profit_change_pct' => $netProfitChange,
                ],
                [
                    'suggested_action_type' => 'RULE_REVIEW',
                    'url' => '/admin/control-tower/drill/profit-leak',
                    'payload' => ['focus' => 'fee_drift'],
                ]
            );
        }

        $algoAlerts = (int) data_get($ops, 'algorithm_alert_count', 0);
        if ($algoAlerts > 0 || $this->algoShiftHeuristic($payload)) {
            $signals[] = $this->signal(
                $tenantId,
                $date,
                'global',
                $marketplace,
                null,
                'warning',
                'ALGO_SHIFT',
                'Potansiyel Algoritma Değişimi',
                'Fiyat/store/stock büyük ölçüde sabitken görünürlük veya kazanım oranlarında sert sapma gözlendi.',
                [
                    'alert_count' => $algoAlerts,
                    'confidence' => $algoAlerts > 0 ? 'medium' : 'low',
                    'heuristic' => $algoAlerts > 0 ? 'shock_records' : 'buybox_vs_profit',
                ],
                [
                    'suggested_action_type' => 'LISTING_OPTIMIZE',
                    'url' => '/admin/control-tower/drill/campaigns',
                    'payload' => ['focus' => 'algo_shift'],
                ]
            );
        }

        $forecast = (float) data_get($cfo, 'cashflow_30d_forecast', 0.0);
        $cashflowTrend = collect((array) data_get($cfo, 'cashflow_trend', []))
            ->pluck('net_profit')
            ->map(fn ($v) => (float) $v)
            ->values();
        $last7 = $cashflowTrend->slice(-7)->avg() ?: 0.0;
        $prev7 = $cashflowTrend->slice(-14, 7)->avg() ?: 0.0;
        if ($forecast < 0 || ((float) $last7 - (float) $prev7) < -100) {
            $signals[] = $this->signal(
                $tenantId,
                $date,
                'global',
                $marketplace,
                null,
                $forecast < 0 ? 'critical' : 'warning',
                'CASHFLOW_RISK',
                'Nakit Akışı Riski',
                '30 günlük projeksiyon negatif veya kısa vadeli trend sert düşüşte.',
                [
                    'cashflow_30d_forecast' => $forecast,
                    'trend_delta_last7_vs_prev7' => (float) $last7 - (float) $prev7,
                ],
                [
                    'suggested_action_type' => 'PRICE_INCREASE',
                    'url' => '/admin/control-tower/drill/profit-leak',
                    'payload' => ['focus' => 'cashflow'],
                ]
            );
        }

        $returnDelta = (float) data_get($ops, 'return_rate_delta_7d.value', 0.0);
        if ($returnDelta >= 0.08 || $returnCosts > 0) {
            $signals[] = $this->signal(
                $tenantId,
                $date,
                'global',
                $marketplace,
                null,
                $returnDelta >= 0.12 ? 'critical' : 'warning',
                'RETURN_SPIKE',
                'İade Oranında Sıçrama',
                'İade oranı ve iade kaynaklı maliyetler yükselişte. Ürün/listing/lojistik aksiyonu önerilir.',
                [
                    'return_rate_delta_7d' => $returnDelta,
                    'return_costs' => $returnCosts,
                ],
                [
                    'suggested_action_type' => 'CUSTOMER_SUPPORT',
                    'url' => '/admin/control-tower/drill/risk',
                    'payload' => ['focus' => 'return_rate'],
                ]
            );
        }

        return $signals;
    }

    public function isAlgorithmShiftLikely(array $payload): bool
    {
        return $this->algoShiftHeuristic($payload);
    }

    private function algoShiftHeuristic(array $payload): bool
    {
        $buyboxTrend = collect((array) data_get($payload, 'ops.buybox_trend', []));
        if ($buyboxTrend->count() < 7) {
            return false;
        }

        $winRates = $buyboxTrend->pluck('win_rate')->map(fn ($v) => (float) $v)->values();
        $recent = (float) ($winRates->slice(-3)->avg() ?? 0.0);
        $baseline = (float) ($winRates->slice(-10, 7)->avg() ?? 0.0);
        $drop = $baseline - $recent;

        $priceWars = (float) data_get($payload, 'cfo.profit_leak_breakdown.price_wars', 0.0);
        $storeDeltas = collect((array) data_get($payload, 'ops.store_score_delta_7d', []))
            ->map(fn ($v) => (float) $v)
            ->values();
        $storeStable = $storeDeltas->isEmpty() || $storeDeltas->every(fn ($v) => abs($v) <= 1.5);

        return $drop >= 0.18 && $storeStable && $priceWars <= 0.0;
    }

    /**
     * @return array<string,mixed>
     */
    private function signal(
        int $tenantId,
        CarbonImmutable $date,
        string $scope,
        ?string $marketplace,
        ?string $sku,
        string $severity,
        string $type,
        string $title,
        string $message,
        array $drivers,
        array $actionHint
    ): array {
        return [
            'tenant_id' => $tenantId,
            'date' => $date->toDateString(),
            'scope' => $scope,
            'marketplace' => $marketplace ?: null,
            'sku' => $sku ?: null,
            'severity' => $severity,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'drivers' => $drivers,
            'action_hint' => $actionHint,
            'is_resolved' => false,
            'resolved_at' => null,
        ];
    }
}

