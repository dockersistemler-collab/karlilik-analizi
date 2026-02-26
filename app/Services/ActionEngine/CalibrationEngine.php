<?php

namespace App\Services\ActionEngine;

use App\Models\ActionEngineCalibration;
use App\Models\MarketplacePriceHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CalibrationEngine
{
    public function runForTenant(
        int $tenantId,
        int $userId,
        CarbonImmutable $asOfDate,
        int $windowDays = 45
    ): array {
        $from = $asOfDate->subDays(max(1, $windowDays - 1));
        $rows = MarketplacePriceHistory::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $asOfDate->toDateString())
            ->orderBy('date')
            ->get();

        $results = [];

        $marketplaces = $rows->pluck('marketplace')->unique()->filter()->values();
        foreach ($marketplaces as $marketplace) {
            $marketRows = $rows->where('marketplace', $marketplace)->values();
            $results[] = $this->fitAndStore($tenantId, $userId, (string) $marketplace, null, $windowDays, $marketRows);
        }

        $results[] = $this->fitAndStore($tenantId, $userId, null, null, $windowDays, $rows);

        return [
            'rows' => count($results),
            'calibrations' => $results,
        ];
    }

    public function resolveFor(int $tenantId, ?string $marketplace, ?string $sku): array
    {
        $marketplace = $marketplace ? strtolower($marketplace) : null;
        $sku = $sku !== null ? trim($sku) : null;

        if ($sku !== null && $sku !== '' && $marketplace !== null) {
            $hit = ActionEngineCalibration::query()
                ->where('tenant_id', $tenantId)
                ->where('marketplace', $marketplace)
                ->where('sku', $sku)
                ->latest('calculated_at')
                ->first();
            if ($hit) {
                return $this->toArray($hit);
            }
        }

        if ($marketplace !== null) {
            $hit = ActionEngineCalibration::query()
                ->where('tenant_id', $tenantId)
                ->where('marketplace', $marketplace)
                ->whereNull('sku')
                ->latest('calculated_at')
                ->first();
            if ($hit) {
                return $this->toArray($hit);
            }
        }

        $hit = ActionEngineCalibration::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('marketplace')
            ->whereNull('sku')
            ->latest('calculated_at')
            ->first();
        if ($hit) {
            return $this->toArray($hit);
        }

        return [
            'elasticity' => -1.2,
            'margin_uplift_factor' => 1.05,
            'ad_pause_revenue_drop_pct' => 12,
            'confidence' => 20,
            'diagnostics' => ['source' => 'default'],
        ];
    }

    private function fitAndStore(
        int $tenantId,
        int $userId,
        ?string $marketplace,
        ?string $sku,
        int $windowDays,
        Collection $rows
    ): array {
        $total = $rows->count();
        $excluded = [
            'campaign_import' => 0,
            'shipping_fee_shock' => 0,
            'outlier' => 0,
            'invalid' => 0,
        ];

        $usable = $rows->filter(function ($row) use (&$excluded): bool {
            if ((string) ($row->promo_source ?? '') === 'import') {
                $excluded['campaign_import']++;
                return false;
            }
            if ((bool) ($row->is_shipping_shock ?? false) || (bool) ($row->is_fee_shock ?? false)) {
                $excluded['shipping_fee_shock']++;
                return false;
            }
            $flags = (array) ($row->shock_flags ?? []);
            if (in_array('OUTLIER_DEMAND', $flags, true) || in_array('OUTLIER_PRICE', $flags, true)) {
                $excluded['outlier']++;
                return false;
            }
            if ((float) $row->unit_price <= 0 || (int) $row->units_sold <= 0) {
                $excluded['invalid']++;
                return false;
            }

            return true;
        })->values();

        [$elasticity, $r2] = $this->estimateElasticity($usable);

        $used = $usable->count();
        $coverage = $total > 0 ? ($used / $total) : 0.0;
        $confidence = max(0, min(100, ($coverage * 60) + ($r2 * 40)));
        $marginUpliftFactor = 1 + min(0.25, max(0.02, abs($elasticity) * 0.05));
        $adPauseDropPct = min(40, max(5, 8 + abs($elasticity) * 2));

        $diagnostics = [
            'total_rows' => $total,
            'used_rows' => $used,
            'excluded' => $excluded,
            'r2_proxy' => round($r2, 4),
        ];

        $record = ActionEngineCalibration::query()
            ->where('tenant_id', $tenantId)
            ->where('marketplace', $marketplace)
            ->where('sku', $sku)
            ->first();

        if ($record) {
            $record->update([
                'user_id' => $userId,
                'window_days' => $windowDays,
                'elasticity' => $elasticity,
                'margin_uplift_factor' => $marginUpliftFactor,
                'ad_pause_revenue_drop_pct' => $adPauseDropPct,
                'confidence' => $confidence,
                'diagnostics' => $diagnostics,
                'calculated_at' => now(),
            ]);
        } else {
            $record = ActionEngineCalibration::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'marketplace' => $marketplace,
                'sku' => $sku,
                'window_days' => $windowDays,
                'elasticity' => $elasticity,
                'margin_uplift_factor' => $marginUpliftFactor,
                'ad_pause_revenue_drop_pct' => $adPauseDropPct,
                'confidence' => $confidence,
                'diagnostics' => $diagnostics,
                'calculated_at' => now(),
            ]);
        }

        return $this->toArray($record);
    }

    private function estimateElasticity(Collection $rows): array
    {
        if ($rows->count() < 2) {
            return [-1.2, 0.0];
        }

        $points = $rows->map(function ($row): array {
            return [
                'x' => log((float) $row->unit_price),
                'y' => log(max(1.0, (float) $row->units_sold)),
            ];
        });

        $meanX = $points->avg('x');
        $meanY = $points->avg('y');

        $num = 0.0;
        $den = 0.0;
        foreach ($points as $p) {
            $num += ($p['x'] - $meanX) * ($p['y'] - $meanY);
            $den += ($p['x'] - $meanX) ** 2;
        }
        if ($den == 0.0) {
            return [-1.2, 0.0];
        }

        $b = $num / $den;
        $a = $meanY - ($b * $meanX);

        $ssRes = 0.0;
        $ssTot = 0.0;
        foreach ($points as $p) {
            $pred = $a + ($b * $p['x']);
            $ssRes += ($p['y'] - $pred) ** 2;
            $ssTot += ($p['y'] - $meanY) ** 2;
        }
        $r2 = $ssTot > 0 ? max(0.0, min(1.0, 1 - ($ssRes / $ssTot))) : 0.0;

        return [$b, $r2];
    }

    private function toArray(ActionEngineCalibration $record): array
    {
        return [
            'id' => $record->id,
            'tenant_id' => $record->tenant_id,
            'marketplace' => $record->marketplace,
            'sku' => $record->sku,
            'elasticity' => (float) $record->elasticity,
            'margin_uplift_factor' => (float) $record->margin_uplift_factor,
            'ad_pause_revenue_drop_pct' => (float) $record->ad_pause_revenue_drop_pct,
            'confidence' => (float) $record->confidence,
            'diagnostics' => (array) ($record->diagnostics ?? []),
        ];
    }
}

