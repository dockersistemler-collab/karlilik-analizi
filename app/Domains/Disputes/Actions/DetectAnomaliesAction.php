<?php

namespace App\Domains\Disputes\Actions;

use App\Domains\Settlements\Models\Dispute;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\SettlementRule;
use App\Domains\Settlements\Rules\SettlementRuleSet;

class DetectAnomaliesAction
{
    public function execute(int $payoutId): void
    {
        $payout = Payout::query()->withoutGlobalScope('tenant_scope')->findOrFail($payoutId);

        $rule = SettlementRule::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $payout->tenant_id)
            ->where('marketplace_integration_id', $payout->marketplace_integration_id)
            ->first();

        $ruleSet = SettlementRuleSet::fromArray($rule?->ruleset ?? []);
        $expected = (float) $payout->expected_amount;
        $actual = (float) ($payout->paid_amount ?? 0);
        $diff = round($expected - $actual, 4);

        if (abs($diff) <= $ruleSet->toleranceAmount) {
            Dispute::query()
                ->where('tenant_id', $payout->tenant_id)
                ->where('payout_id', $payout->id)
                ->delete();
            return;
        }

        Dispute::query()->updateOrCreate(
            [
                'tenant_id' => $payout->tenant_id,
                'payout_id' => $payout->id,
                'dispute_type' => 'MISSING_PAYMENT',
            ],
            [
                'expected_amount' => $expected,
                'actual_amount' => $actual,
                'diff_amount' => $diff,
                'status' => 'OPEN',
                'evidence' => [
                    'reconciliation_snapshot' => [
                        'expected' => $expected,
                        'actual' => $actual,
                        'diff' => $diff,
                    ],
                ],
                'notes' => 'Auto-detected from reconciliation discrepancy.',
            ]
        );
    }
}

