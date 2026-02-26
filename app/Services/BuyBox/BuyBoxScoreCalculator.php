<?php

namespace App\Services\BuyBox;

use App\Models\BuyBoxScoringProfile;
use App\Models\MarketplaceOfferSnapshot;
use Carbon\Carbon;

class BuyBoxScoreCalculator
{
    private const DEFAULT_WEIGHTS = [
        'price_competitiveness' => 35.0,
        'store_score' => 25.0,
        'shipping_speed' => 20.0,
        'stock' => 10.0,
        'promo' => 10.0,
    ];

    private const DEFAULT_THRESHOLDS = [
        'risky' => 60,
    ];

    /**
     * @return array{buybox_score:int,status:string,win_probability:float,drivers:array<int,array<string,mixed>>,components:array<string,float>}
     */
    public function calculate(MarketplaceOfferSnapshot $snapshot, ?BuyBoxScoringProfile $profile = null): array
    {
        $profile ??= BuyBoxScoringProfile::query()
            ->where('tenant_id', $snapshot->tenant_id)
            ->where('marketplace', $snapshot->marketplace)
            ->first();

        $weights = $this->resolveWeights($profile?->weights ?? null);
        $thresholds = $this->resolveThresholds($profile?->thresholds ?? null);

        $components = [
            'price_competitiveness' => $this->priceComponent($snapshot),
            'store_score' => $this->storeScoreComponent($snapshot),
            'shipping_speed' => $this->shippingComponent($snapshot),
            'stock' => $this->stockComponent($snapshot),
            'promo' => $this->promoComponent($snapshot),
        ];

        $weightTotal = array_sum($weights) ?: 1.0;
        $scoreFloat = 0.0;
        $penalties = [];
        foreach ($components as $key => $componentScore) {
            $weight = (float) ($weights[$key] ?? 0);
            $points = $weight * ($componentScore / 100);
            $penaltyPoints = $weight * ((100 - $componentScore) / 100);
            $penalties[$key] = [
                'metric' => $key,
                'component_score' => round($componentScore, 2),
                'weight' => round($weight, 2),
                'penalty' => round($penaltyPoints, 2),
            ];
            $scoreFloat += $points;
        }

        $normalizedScore = (int) round(max(0, min(100, ($scoreFloat / $weightTotal) * 100)));

        uasort($penalties, fn (array $a, array $b): int => $b['penalty'] <=> $a['penalty']);
        $drivers = array_values(array_slice($penalties, 0, 3));

        $status = 'losing';
        if ((bool) $snapshot->is_winning) {
            $status = 'winning';
        } elseif ($normalizedScore >= (int) ($thresholds['risky'] ?? 60)) {
            $status = 'risky';
        }

        $winProbability = max(0.0, min(1.0, (($normalizedScore + ($snapshot->is_winning ? 5 : 0)) / 100)));

        return [
            'buybox_score' => $normalizedScore,
            'status' => $status,
            'win_probability' => round($winProbability, 4),
            'drivers' => $drivers,
            'components' => $components,
        ];
    }

    private function priceComponent(MarketplaceOfferSnapshot $snapshot): float
    {
        $our = $snapshot->our_price !== null ? (float) $snapshot->our_price : null;
        $best = $snapshot->competitor_best_price !== null ? (float) $snapshot->competitor_best_price : null;
        if ($our === null || $best === null || $best <= 0) {
            return 50.0;
        }

        $gap = ($our - $best) / $best;
        if ($gap <= 0) {
            return 100.0;
        }
        if ($gap <= 0.05) {
            return max(40.0, 80.0 - (($gap / 0.05) * 40.0));
        }

        return 10.0;
    }

    private function storeScoreComponent(MarketplaceOfferSnapshot $snapshot): float
    {
        $raw = $snapshot->store_score !== null ? (float) $snapshot->store_score : null;
        if ($raw === null) {
            $base = 50.0;
        } elseif ($raw <= 10.0) {
            $base = $raw * 10.0;
        } else {
            $base = $raw;
        }

        $base = max(0.0, min(100.0, $base));

        $date = Carbon::parse($snapshot->date)->startOfDay();
        $recentAvg = MarketplaceOfferSnapshot::query()
            ->where('tenant_id', $snapshot->tenant_id)
            ->where('marketplace', $snapshot->marketplace)
            ->where('sku', $snapshot->sku)
            ->whereDate('date', '<=', $date->toDateString())
            ->whereDate('date', '>=', $date->copy()->subDays(6)->toDateString())
            ->avg('store_score');

        $monthlyAvg = MarketplaceOfferSnapshot::query()
            ->where('tenant_id', $snapshot->tenant_id)
            ->where('marketplace', $snapshot->marketplace)
            ->where('sku', $snapshot->sku)
            ->whereDate('date', '<=', $date->toDateString())
            ->whereDate('date', '>=', $date->copy()->subDays(29)->toDateString())
            ->avg('store_score');

        if ($recentAvg === null || $monthlyAvg === null) {
            return $base;
        }

        $recent = (float) $recentAvg;
        $monthly = (float) $monthlyAvg;
        if ($monthly <= 0 || $recent >= $monthly) {
            return $base;
        }

        $dropRatio = ($monthly - $recent) / $monthly;
        $penalty = min(20.0, $dropRatio * 100.0);

        return max(0.0, $base - $penalty);
    }

    private function shippingComponent(MarketplaceOfferSnapshot $snapshot): float
    {
        if ($snapshot->shipping_speed_score === null) {
            return 50.0;
        }
        $value = (float) $snapshot->shipping_speed_score;
        if ($value <= 1.0) {
            $value *= 100.0;
        }

        return max(0.0, min(100.0, $value));
    }

    private function stockComponent(MarketplaceOfferSnapshot $snapshot): float
    {
        if ($snapshot->stock_available === null) {
            return 50.0;
        }
        $stock = (int) $snapshot->stock_available;
        if ($stock <= 0) {
            return 0.0;
        }
        if ($stock <= 3) {
            return 40.0;
        }

        return 100.0;
    }

    private function promoComponent(MarketplaceOfferSnapshot $snapshot): float
    {
        return $snapshot->promo_flag ? 100.0 : 0.0;
    }

    /**
     * @param array<string,mixed>|null $weights
     * @return array<string,float>
     */
    private function resolveWeights(?array $weights): array
    {
        $out = self::DEFAULT_WEIGHTS;
        if (!is_array($weights)) {
            return $out;
        }

        foreach (array_keys(self::DEFAULT_WEIGHTS) as $key) {
            if (isset($weights[$key]) && is_numeric($weights[$key])) {
                $out[$key] = max(0.0, (float) $weights[$key]);
            }
        }

        return $out;
    }

    /**
     * @param array<string,mixed>|null $thresholds
     * @return array<string,int>
     */
    private function resolveThresholds(?array $thresholds): array
    {
        $out = self::DEFAULT_THRESHOLDS;
        if (!is_array($thresholds)) {
            return $out;
        }
        if (isset($thresholds['risky']) && is_numeric($thresholds['risky'])) {
            $out['risky'] = (int) max(0, min(100, (int) $thresholds['risky']));
        }

        return $out;
    }
}
