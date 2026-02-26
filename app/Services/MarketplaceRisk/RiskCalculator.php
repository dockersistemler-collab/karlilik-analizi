<?php

namespace App\Services\MarketplaceRisk;

use App\Models\MarketplaceKpiSnapshot;
use App\Models\MarketplaceRiskProfile;
use Carbon\CarbonImmutable;

class RiskCalculator
{
    public function calculate(
        int $tenantId,
        string $marketplace,
        CarbonImmutable $date,
        MarketplaceKpiSnapshot $snapshot,
        MarketplaceRiskProfile $profile
    ): array {
        $weights = is_array($profile->weights) ? $profile->weights : [];
        $metricThresholds = is_array($profile->metric_thresholds) ? $profile->metric_thresholds : [];
        $statusThresholds = is_array($profile->thresholds) ? $profile->thresholds : ['warning' => 45, 'critical' => 70];

        $presentWeights = [];
        $metricSeverity = [];
        $weightedContribution = [];
        $missingMetrics = [];

        foreach ($weights as $metric => $weight) {
            $value = $this->readMetricValue($snapshot, (string) $metric);
            if ($value === null) {
                $missingMetrics[] = (string) $metric;
                continue;
            }

            $presentWeights[(string) $metric] = (float) $weight;
            $metricConfig = $metricThresholds[(string) $metric] ?? [];
            $severity = $this->severityFromThresholds($value, $metricConfig);
            $metricSeverity[(string) $metric] = $severity;
        }

        $weightTotal = array_sum($presentWeights);
        if ($weightTotal <= 0) {
            return [
                'risk_score' => 0.0,
                'status' => 'ok',
                'reasons' => [
                    'drivers' => [],
                    'trends' => [],
                    'missing_metrics' => $missingMetrics,
                ],
            ];
        }

        $riskScore = 0.0;
        foreach ($presentWeights as $metric => $weight) {
            $normalizedWeight = $weight / $weightTotal;
            $contribution = $metricSeverity[$metric] * $normalizedWeight;
            $weightedContribution[$metric] = $contribution;
            $riskScore += $contribution;
        }

        arsort($weightedContribution);
        $drivers = collect($weightedContribution)
            ->take(3)
            ->map(function (float $contribution, string $metric) use ($metricSeverity): array {
                return [
                    'metric' => $metric,
                    'severity' => round((float) ($metricSeverity[$metric] ?? 0), 2),
                    'contribution' => round($contribution, 2),
                ];
            })
            ->values()
            ->all();

        $trends = $this->buildTrendReasons($tenantId, $marketplace, $date, $metricThresholds);

        $criticalAt = (float) ($statusThresholds['critical'] ?? 70);
        $warningAt = (float) ($statusThresholds['warning'] ?? 45);

        $status = 'ok';
        if ($riskScore >= $criticalAt) {
            $status = 'critical';
        } elseif ($riskScore >= $warningAt) {
            $status = 'warning';
        }

        return [
            'risk_score' => round($riskScore, 4),
            'status' => $status,
            'reasons' => [
                'drivers' => $drivers,
                'trends' => $trends,
                'missing_metrics' => $missingMetrics,
            ],
        ];
    }

    private function severityFromThresholds(float $value, array $metricConfig): float
    {
        $warning = isset($metricConfig['warning']) ? (float) $metricConfig['warning'] : null;
        $critical = isset($metricConfig['critical']) ? (float) $metricConfig['critical'] : null;
        $direction = (string) ($metricConfig['direction'] ?? 'higher_worse');

        if ($warning === null || $critical === null) {
            return 0.0;
        }

        if ($direction === 'lower_worse') {
            if ($value >= $warning) {
                return 0.0;
            }
            if ($value <= $critical) {
                return 100.0;
            }

            $span = $warning - $critical;
            if ($span <= 0) {
                return 0.0;
            }

            return (($warning - $value) / $span) * 100;
        }

        if ($value <= $warning) {
            return 0.0;
        }
        if ($value >= $critical) {
            return 100.0;
        }

        $span = $critical - $warning;
        if ($span <= 0) {
            return 0.0;
        }

        return (($value - $warning) / $span) * 100;
    }

    private function readMetricValue(MarketplaceKpiSnapshot $snapshot, string $metric): ?float
    {
        $value = $snapshot->{$metric} ?? null;
        if ($value === null) {
            return null;
        }

        return (float) $value;
    }

    private function buildTrendReasons(
        int $tenantId,
        string $marketplace,
        CarbonImmutable $date,
        array $metricThresholds
    ): array {
        $baseQuery = MarketplaceKpiSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('marketplace', strtolower($marketplace))
            ->whereDate('date', '<=', $date->toDateString());

        $recent = (clone $baseQuery)
            ->whereDate('date', '>=', $date->subDays(6)->toDateString())
            ->get();
        $monthly = (clone $baseQuery)
            ->whereDate('date', '>=', $date->subDays(29)->toDateString())
            ->get();

        $metrics = array_keys($metricThresholds);
        $trends = [];
        foreach ($metrics as $metric) {
            $recentValues = $recent->pluck($metric)->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
            $monthlyValues = $monthly->pluck($metric)->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);

            if ($recentValues->isEmpty() || $monthlyValues->isEmpty()) {
                continue;
            }

            $avg7 = (float) $recentValues->avg();
            $avg30 = (float) $monthlyValues->avg();
            $direction = (string) ($metricThresholds[$metric]['direction'] ?? 'higher_worse');
            $worsening = $direction === 'lower_worse' ? ($avg7 < $avg30) : ($avg7 > $avg30);
            if (!$worsening) {
                continue;
            }

            $delta = abs($avg7 - $avg30);
            if ($delta < 0.001) {
                continue;
            }

            $trends[] = [
                'metric' => $metric,
                'avg_7d' => round($avg7, 4),
                'avg_30d' => round($avg30, 4),
                'delta' => round($delta, 4),
                'direction' => $direction,
            ];
        }

        usort($trends, fn (array $a, array $b): int => $b['delta'] <=> $a['delta']);

        return array_slice($trends, 0, 3);
    }
}

