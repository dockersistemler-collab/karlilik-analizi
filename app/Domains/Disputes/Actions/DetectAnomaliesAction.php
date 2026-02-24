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

        $disputeType = $this->detectType($payoutId, $actual, $diff, $ruleSet->toleranceAmount);

        Dispute::query()
            ->where('tenant_id', $payout->tenant_id)
            ->where('payout_id', $payout->id)
            ->where('status', 'OPEN')
            ->delete();

        Dispute::query()->create([
            'tenant_id' => $payout->tenant_id,
            'payout_id' => $payout->id,
            'dispute_type' => $disputeType,
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
        ]);
    }

    private function detectType(int $payoutId, float $actual, float $diff, float $tolerance): string
    {
        if ($actual <= 0.0) {
            return 'MISSING_PAYMENT';
        }

        $transactions = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->with('transactions:id,payout_id,type,amount,vat_amount')
            ->findOrFail($payoutId)
            ->transactions;

        $commissionAmount = (float) $transactions
            ->filter(fn ($tx) => str_contains(strtoupper((string) $tx->type), 'COMMISSION'))
            ->sum('amount');
        if ($commissionAmount !== 0.0 && abs(abs($diff) - abs($commissionAmount)) <= $tolerance) {
            return 'COMMISSION_DIFF';
        }

        $shippingAmount = (float) $transactions
            ->filter(function ($tx) {
                $type = strtoupper((string) $tx->type);
                return str_contains($type, 'SHIPPING') || str_contains($type, 'CARGO');
            })
            ->sum('amount');
        if ($shippingAmount !== 0.0 && abs(abs($diff) - abs($shippingAmount)) <= $tolerance) {
            return 'SHIPPING_DIFF';
        }

        $vatAmount = (float) $transactions->sum('vat_amount');
        if ($vatAmount !== 0.0 && abs(abs($diff) - abs($vatAmount)) <= $tolerance) {
            return 'VAT_DIFF';
        }

        return 'UNKNOWN_DEDUCTION';
    }
}
