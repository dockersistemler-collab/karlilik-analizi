<?php

namespace Tests\Unit;

use App\Domains\Settlements\Services\LossFinderEngine;
use Tests\TestCase;

class LossFinderEngineTest extends TestCase
{
    public function test_commission_high_rule_detects_extra_cut(): void
    {
        $engine = app(LossFinderEngine::class);
        $findings = $engine->analyze(
            ['commission' => -10],
            ['commission' => -25],
            [['type' => 'commission', 'gross_amount' => -30, 'vat_amount' => -5, 'net_amount' => -25]],
            [],
            true,
            true
        );

        $codes = collect($findings)->pluck('code')->all();
        $this->assertContains('LOSS_COMMISSION_HIGH', $codes);
    }

    public function test_vat_mismatch_rule_detects_inconsistent_row(): void
    {
        $engine = app(LossFinderEngine::class);
        $findings = $engine->analyze(
            ['sale' => 100],
            ['sale' => 100],
            [['type' => 'sale', 'gross_amount' => 120, 'vat_amount' => 20, 'net_amount' => 70]],
            [],
            true,
            true
        );

        $this->assertTrue(collect($findings)->contains(fn ($f) => $f['code'] === 'LOSS_VAT_MISMATCH'));
    }

    public function test_shipping_dup_or_high_rule_detects_multiple_rows(): void
    {
        $engine = app(LossFinderEngine::class);
        $findings = $engine->analyze(
            ['shipping' => 10],
            ['shipping' => 10],
            [
                ['type' => 'shipping', 'gross_amount' => 6, 'vat_amount' => 1, 'net_amount' => 5],
                ['type' => 'shipping', 'gross_amount' => 6, 'vat_amount' => 1, 'net_amount' => 5],
            ],
            [],
            true,
            true
        );

        $this->assertTrue(collect($findings)->contains(fn ($f) => $f['code'] === 'LOSS_SHIPPING_DUP_OR_HIGH'));
    }

    public function test_micro_loss_aggregator_groups_small_losses(): void
    {
        $engine = app(LossFinderEngine::class);
        $findings = $engine->analyze(
            [],
            [],
            [
                ['type' => 'other', 'gross_amount' => 1.20, 'vat_amount' => 0.20, 'net_amount' => 1.00],
                ['type' => 'other', 'gross_amount' => 1.20, 'vat_amount' => 0.20, 'net_amount' => 1.00],
                ['type' => 'other', 'gross_amount' => 1.20, 'vat_amount' => 0.20, 'net_amount' => 1.00],
            ],
            [],
            false,
            true
        );

        $this->assertTrue(collect($findings)->contains(fn ($f) => $f['code'] === 'MICRO_LOSS_AGGREGATOR'));
    }
}

