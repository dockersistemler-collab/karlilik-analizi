<?php

namespace App\Services\ActionEngine;

use App\Models\MarketplaceExternalShock;
use App\Models\MarketplaceKpiSnapshot;
use App\Models\MarketplacePriceHistory;
use App\Models\OrderProfitSnapshot;
use Carbon\CarbonImmutable;

class ShockDetector
{
    public function detectForTenant(int $tenantId, int $userId, CarbonImmutable $asOfDate, int $windowDays = 45): array
    {
        $from = $asOfDate->subDays(max(1, $windowDays - 1));
        $history = MarketplacePriceHistory::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $asOfDate->toDateString())
            ->orderBy('date')
            ->get();

        $detected = 0;
        foreach ($history as $row) {
            $date = CarbonImmutable::parse($row->date);
            $flags = (array) ($row->shock_flags ?? []);

            $prev7 = MarketplacePriceHistory::query()
                ->where('tenant_id', $tenantId)
                ->where('marketplace', $row->marketplace)
                ->where('sku', $row->sku)
                ->whereDate('date', '<', $date->toDateString())
                ->whereDate('date', '>=', $date->subDays(7)->toDateString())
                ->get();

            $avgPrice7 = (float) ($prev7->avg('unit_price') ?? 0);
            $avgUnits7 = (float) ($prev7->avg('units_sold') ?? 0);
            if ($avgPrice7 > 0 && $avgUnits7 > 0 && (float) $row->unit_price <= $avgPrice7 * 0.9 && (float) $row->units_sold >= $avgUnits7 * 1.2) {
                if ((string) ($row->promo_source ?? '') !== 'import') {
                    $row->is_promo_day = true;
                    $row->promo_source = 'heuristic';
                }
                $flags[] = 'CAMPAIGN';
                $detected += $this->upsertShock($tenantId, $userId, $row->marketplace, $row->sku, $date, 'CAMPAIGN', 'medium', 'heuristic', [
                    'price_drop_ratio' => $avgPrice7 > 0 ? ((float) $row->unit_price / $avgPrice7) : null,
                    'units_jump_ratio' => $avgUnits7 > 0 ? ((float) $row->units_sold / $avgUnits7) : null,
                ]);
            }

            $prev30 = MarketplacePriceHistory::query()
                ->where('tenant_id', $tenantId)
                ->where('marketplace', $row->marketplace)
                ->where('sku', $row->sku)
                ->whereDate('date', '<', $date->toDateString())
                ->whereDate('date', '>=', $date->subDays(30)->toDateString())
                ->get();
            if ($prev30->count() >= 8) {
                $unitsMean = (float) ($prev30->avg('units_sold') ?? 0);
                $priceMean = (float) ($prev30->avg('unit_price') ?? 0);
                $unitsStd = $this->std($prev30->pluck('units_sold')->map(fn ($v) => (float) $v)->all());
                $priceStd = $this->std($prev30->pluck('unit_price')->map(fn ($v) => (float) $v)->all());

                if ($unitsStd > 0 && abs(((float) $row->units_sold - $unitsMean) / $unitsStd) >= 2.5) {
                    $flags[] = 'OUTLIER_DEMAND';
                    $detected += $this->upsertShock($tenantId, $userId, $row->marketplace, $row->sku, $date, 'OUTLIER_DEMAND', 'high', 'heuristic', []);
                }
                if ($priceStd > 0 && abs(((float) $row->unit_price - $priceMean) / $priceStd) >= 2.5) {
                    $flags[] = 'OUTLIER_PRICE';
                    $detected += $this->upsertShock($tenantId, $userId, $row->marketplace, $row->sku, $date, 'OUTLIER_PRICE', 'high', 'heuristic', []);
                }
            }

            $row->shock_flags = array_values(array_unique($flags));
            $row->save();
        }

        $marketplaces = $history->pluck('marketplace')->unique()->filter();
        foreach ($marketplaces as $marketplace) {
            $kpiRows = MarketplaceKpiSnapshot::query()
                ->where('tenant_id', $tenantId)
                ->where('marketplace', $marketplace)
                ->whereDate('date', '>=', $from->toDateString())
                ->whereDate('date', '<=', $asOfDate->toDateString())
                ->orderBy('date')
                ->get();

            foreach ($kpiRows as $kpi) {
                $date = CarbonImmutable::parse($kpi->date);
                $prev = MarketplaceKpiSnapshot::query()
                    ->where('tenant_id', $tenantId)
                    ->where('marketplace', $marketplace)
                    ->whereDate('date', '<', $date->toDateString())
                    ->whereDate('date', '>=', $date->subDays(7)->toDateString())
                    ->get();

                $avgLate = (float) ($prev->avg('late_shipment_rate') ?? 0);
                $avgPerf = (float) ($prev->avg('performance_score') ?? 0);
                if ($avgLate > 0 && (float) $kpi->late_shipment_rate >= ($avgLate * 1.5) && (float) $kpi->performance_score <= ($avgPerf - 5)) {
                    $detected += $this->upsertShock($tenantId, $userId, $marketplace, null, $date, 'SHIPPING_CHANGE', 'high', 'heuristic', []);
                    MarketplacePriceHistory::query()
                        ->where('tenant_id', $tenantId)
                        ->where('marketplace', $marketplace)
                        ->whereDate('date', $date->toDateString())
                        ->update(['is_shipping_shock' => true]);
                }
            }

            $marginRows = OrderProfitSnapshot::query()
                ->where('tenant_id', $tenantId)
                ->where('marketplace', $marketplace)
                ->whereHas('order', fn ($q) => $q->whereDate('order_date', '>=', $from->toDateString())->whereDate('order_date', '<=', $asOfDate->toDateString()))
                ->get();
            $dates = $marginRows->map(fn ($r) => $r->order?->order_date?->toDateString())->filter()->unique();
            foreach ($dates as $d) {
                $dayMargins = $marginRows->filter(fn ($r) => $r->order?->order_date?->toDateString() === $d);
                $avgMarginDay = (float) ($dayMargins->avg('net_margin') ?? 0);
                $day = CarbonImmutable::parse($d);
                $prevMargins = $marginRows->filter(fn ($r) => ($od = $r->order?->order_date?->toDateString()) && $od < $d && $od >= $day->subDays(7)->toDateString());
                $avgMarginPrev = (float) ($prevMargins->avg('net_margin') ?? 0);
                if ($avgMarginPrev !== 0.0 && ($avgMarginDay <= ($avgMarginPrev - 5))) {
                    $detected += $this->upsertShock($tenantId, $userId, $marketplace, null, $day, 'FEE_CHANGE', 'medium', 'heuristic', []);
                    MarketplacePriceHistory::query()
                        ->where('tenant_id', $tenantId)
                        ->where('marketplace', $marketplace)
                        ->whereDate('date', $d)
                        ->update(['is_fee_shock' => true]);
                }
            }
        }

        return ['detected' => $detected];
    }

    private function upsertShock(
        int $tenantId,
        int $userId,
        string $marketplace,
        ?string $sku,
        CarbonImmutable $date,
        string $type,
        string $severity,
        string $detectedBy,
        array $details
    ): int {
        MarketplaceExternalShock::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'marketplace' => strtolower($marketplace),
                'sku' => $sku,
                'date' => $date->toDateString(),
                'shock_type' => $type,
                'detected_by' => $detectedBy,
            ],
            [
                'user_id' => $userId,
                'severity' => $severity,
                'details' => $details,
            ]
        );

        return 1;
    }

    private function std(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }
        $mean = array_sum($values) / $n;
        $sum = 0.0;
        foreach ($values as $v) {
            $sum += ($v - $mean) ** 2;
        }

        return sqrt($sum / ($n - 1));
    }
}

