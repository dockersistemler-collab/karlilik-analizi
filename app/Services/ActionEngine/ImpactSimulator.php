<?php

namespace App\Services\ActionEngine;

use App\Domains\Settlements\Models\OrderItem;
use App\Models\ActionRecommendation;
use App\Models\ActionRecommendationImpact;
use App\Models\MarketplacePriceHistory;
use App\Models\OrderProfitSnapshot;

class ImpactSimulator
{
    public function __construct(
        private readonly CalibrationEngine $calibrations
    ) {
    }

    public function simulateAndStore(ActionRecommendation $recommendation): ActionRecommendationImpact
    {
        $tenantId = (int) $recommendation->tenant_id;
        $marketplace = strtolower((string) $recommendation->marketplace);
        $sku = trim((string) ($recommendation->sku ?? ''));

        $baseline = $this->baseline($recommendation);
        $calibration = $this->calibrations->resolveFor($tenantId, $marketplace, $sku !== '' ? $sku : null);

        $scenario = [
            'action_type' => $recommendation->action_type,
            'marketplace' => $marketplace,
            'sku' => $sku,
        ];
        $expected = [];
        $delta = [];
        $riskEffect = 0.0;

        if ($recommendation->action_type === 'PRICE_INCREASE') {
            $increasePct = (float) (data_get($recommendation->suggested_payload, 'target_price_increase_pct', 5));
            $elasticity = (float) ($calibration['elasticity'] ?? -1.2);
            $upliftFactor = (float) ($calibration['margin_uplift_factor'] ?? 1.05);

            $basePrice = (float) ($baseline['unit_price'] ?? 0);
            $baseUnits = (float) ($baseline['units_sold'] ?? 0);
            $baseRevenue = (float) ($baseline['revenue'] ?? 0);
            $baseProfit = (float) ($baseline['net_profit'] ?? 0);
            $baseMargin = $baseRevenue > 0 ? $baseProfit / $baseRevenue : 0.0;

            $newPrice = $basePrice * (1 + ($increasePct / 100));
            $unitsFactor = max(0.0, 1 + ($elasticity * ($increasePct / 100)));
            $newUnits = $baseUnits * $unitsFactor;
            $newRevenue = $newPrice * $newUnits;
            $newProfit = $newRevenue * $baseMargin * $upliftFactor;

            $expected = [
                'unit_price' => $newPrice,
                'units_sold' => $newUnits,
                'revenue' => $newRevenue,
                'net_profit' => $newProfit,
            ];
            $delta = [
                'revenue' => $newRevenue - $baseRevenue,
                'net_profit' => $newProfit - $baseProfit,
            ];
            $riskEffect = -8.0;
            $scenario['price_increase_pct'] = $increasePct;
        } elseif ($recommendation->action_type === 'PRICE_ADJUST') {
            $targetPrice = (float) (data_get($recommendation->suggested_payload, 'target_price', 0));
            $currentPrice = (float) (data_get($recommendation->suggested_payload, 'current_price', 0));
            if ($targetPrice <= 0) {
                $targetPrice = $currentPrice > 0 ? $currentPrice : (float) ($baseline['unit_price'] ?? 0);
            }
            if ($currentPrice <= 0) {
                $currentPrice = (float) ($baseline['unit_price'] ?? $targetPrice);
            }

            $elasticity = (float) ($calibration['elasticity'] ?? -1.2);
            $baseUnits = (float) ($baseline['units_sold'] ?? 0);
            $baseRevenue = (float) ($baseline['revenue'] ?? 0);
            $baseProfit = (float) ($baseline['net_profit'] ?? 0);
            $baseMargin = $baseRevenue > 0 ? $baseProfit / $baseRevenue : 0.0;
            $priceChangePct = $currentPrice > 0 ? (($targetPrice - $currentPrice) / $currentPrice) : 0.0;

            $unitsFactor = max(0.0, 1 + ($elasticity * $priceChangePct));
            $newUnits = $baseUnits * $unitsFactor;
            $newRevenue = $targetPrice * $newUnits;
            $newProfit = $newRevenue * $baseMargin;

            $expected = [
                'unit_price' => $targetPrice,
                'units_sold' => $newUnits,
                'revenue' => $newRevenue,
                'net_profit' => $newProfit,
                'win_probability' => min(1.0, max(0.0, (float) data_get($recommendation->reason, 'buybox_score', 50) / 100 + 0.10)),
            ];
            $delta = [
                'revenue' => $newRevenue - $baseRevenue,
                'net_profit' => $newProfit - $baseProfit,
                'win_probability' => 0.10,
            ];
            $riskEffect = -6.0;
            $scenario['target_price'] = $targetPrice;
            $scenario['price_change_pct'] = round($priceChangePct * 100, 4);
        } elseif ($recommendation->action_type === 'LISTING_SUSPEND') {
            $baseProfit = (float) ($baseline['net_profit'] ?? 0);
            $lossAvoidance = max(0.0, -$baseProfit);
            $expected = [
                'loss_avoidance' => $lossAvoidance,
                'net_profit' => 0.0,
            ];
            $delta = [
                'net_profit' => $lossAvoidance,
            ];
            $riskEffect = -15.0;
        } else {
            $baseRevenue = (float) ($baseline['revenue'] ?? 0);
            $baseProfit = (float) ($baseline['net_profit'] ?? 0);
            $expected = [
                'revenue' => $baseRevenue * 1.01,
                'net_profit' => $baseProfit * 1.03,
            ];
            $delta = [
                'revenue' => $expected['revenue'] - $baseRevenue,
                'net_profit' => $expected['net_profit'] - $baseProfit,
            ];
            $riskEffect = -4.0;
        }

        $confidence = min(100.0, max(0.0, (float) ($calibration['confidence'] ?? 20)));

        return ActionRecommendationImpact::query()->updateOrCreate(
            ['recommendation_id' => $recommendation->id],
            [
                'baseline' => $baseline,
                'scenario' => $scenario,
                'expected' => $expected,
                'delta' => $delta,
                'confidence' => $confidence,
                'assumptions' => [
                    'calibration' => $calibration,
                    'promo_excluded' => true,
                    'shock_adjusted' => true,
                ],
                'risk_effect' => $riskEffect,
                'calculated_at' => now(),
            ]
        );
    }

    private function baseline(ActionRecommendation $recommendation): array
    {
        $marketplace = strtolower((string) $recommendation->marketplace);
        $sku = trim((string) ($recommendation->sku ?? ''));

        $history = MarketplacePriceHistory::query()
            ->where('tenant_id', $recommendation->tenant_id)
            ->where('marketplace', $marketplace)
            ->when($sku !== '', fn ($q) => $q->where('sku', $sku))
            ->whereDate('date', '<=', $recommendation->date)
            ->orderByDesc('date')
            ->limit(30)
            ->get();

        $revenue = (float) ($history->avg('revenue') ?? 0);
        $units = (float) ($history->avg('units_sold') ?? 0);
        $unitPrice = (float) ($history->avg('unit_price') ?? 0);

        $profitQuery = OrderProfitSnapshot::query()
            ->where('tenant_id', $recommendation->tenant_id)
            ->where('marketplace', $marketplace)
            ->whereHas('order', fn ($q) => $q->whereDate('order_date', '<=', $recommendation->date));
        if ($sku !== '') {
            $profitQuery->whereHas('order.orderItems', fn ($q) => $q->where('sku', $sku));
        }
        $netProfit = (float) ($profitQuery->limit(30)->avg('net_profit') ?? 0);

        return [
            'unit_price' => $unitPrice,
            'units_sold' => $units,
            'revenue' => $revenue,
            'net_profit' => $netProfit,
        ];
    }
}
