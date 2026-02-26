<?php

namespace Tests\Unit\ControlTower;

use App\Services\ControlTower\SignalEngine;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class SignalEngineTest extends TestCase
{
    public function test_profit_leak_signal_is_generated(): void
    {
        $engine = app(SignalEngine::class);
        $payload = [
            'meta' => ['marketplace' => null],
            'cfo' => [
                'net_profit_change_pct' => -21.4,
                'profit_leak_breakdown' => [
                    'campaign_erosion' => 3200.0,
                    'return_costs' => 1200.0,
                    'price_wars' => 600.0,
                    'fee_drifts' => 300.0,
                ],
                'avg_margin_30d' => 7.2,
                'cashflow_30d_forecast' => 1000.0,
                'cashflow_trend' => [],
            ],
            'ops' => [
                'buybox_win_rate_overall' => 0.6,
                'losing_sku_count' => 1,
                'store_score_delta_7d' => [],
                'late_shipments_delta_7d' => ['value' => 0.01],
                'return_rate_delta_7d' => ['value' => 0.01],
                'algorithm_alert_count' => 0,
                'buybox_trend' => [],
            ],
            'campaigns' => ['campaign_count' => 3, 'import_campaign_count' => 1, 'algo_shift_count' => 0],
            'risk' => [],
        ];

        $signals = $engine->generateSignals(10, CarbonImmutable::parse('2026-02-26'), $payload);

        $this->assertNotEmpty($signals);
        $profitLeak = collect($signals)->firstWhere('type', 'PROFIT_LEAK');
        $this->assertNotNull($profitLeak);
        $this->assertSame('critical', data_get($profitLeak, 'severity'));
    }

    public function test_algo_shift_tolerance_logic_detects_possible_shift(): void
    {
        $engine = app(SignalEngine::class);
        $payload = [
            'cfo' => [
                'profit_leak_breakdown' => ['price_wars' => 0.0],
            ],
            'ops' => [
                'buybox_trend' => [
                    ['win_rate' => 0.66],
                    ['win_rate' => 0.64],
                    ['win_rate' => 0.65],
                    ['win_rate' => 0.63],
                    ['win_rate' => 0.61],
                    ['win_rate' => 0.58],
                    ['win_rate' => 0.55],
                    ['win_rate' => 0.40],
                    ['win_rate' => 0.36],
                    ['win_rate' => 0.33],
                ],
                'store_score_delta_7d' => [
                    'trendyol' => -0.4,
                    'amazon' => 0.5,
                ],
            ],
        ];

        $this->assertTrue($engine->isAlgorithmShiftLikely($payload));
    }
}

