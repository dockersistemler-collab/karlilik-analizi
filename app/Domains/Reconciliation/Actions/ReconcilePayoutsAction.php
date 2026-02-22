<?php

namespace App\Domains\Reconciliation\Actions;

use App\Domains\Disputes\Actions\DetectAnomaliesAction;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\Reconciliation;
use App\Domains\Settlements\Models\SettlementRule;
use App\Domains\Settlements\Rules\SettlementRuleSet;
use App\Models\MarketplaceAccount;
use Illuminate\Support\Carbon;

class ReconcilePayoutsAction
{
    public function __construct(private readonly DetectAnomaliesAction $detectAnomaliesAction)
    {
    }

    public function execute(int $accountId): void
    {
        $account = MarketplaceAccount::query()->findOrFail($accountId);

        $payouts = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $account->tenant_id)
            ->where('marketplace_account_id', $account->id)
            ->get();

        foreach ($payouts as $payout) {
            $rule = SettlementRule::query()
                ->withoutGlobalScope('tenant_scope')
                ->where('tenant_id', $payout->tenant_id)
                ->where('marketplace_integration_id', $payout->marketplace_integration_id)
                ->first();

            $ruleSet = SettlementRuleSet::fromArray($rule?->ruleset ?? []);
            $expectedAmount = (float) $payout->expected_amount;
            $paidAmount = (float) ($payout->paid_amount ?? 0);
            $diff = round($expectedAmount - $paidAmount, 4);

            $matchMethod = $payout->payout_reference ? 'REFERENCE' : 'HEURISTIC';
            if (!$payout->payout_reference) {
                $near = Payout::query()
                    ->withoutGlobalScope('tenant_scope')
                    ->where('tenant_id', $payout->tenant_id)
                    ->where('id', '!=', $payout->id)
                    ->whereBetween('period_end', [
                        Carbon::parse($payout->period_end)->subDays(7)->toDateString(),
                        Carbon::parse($payout->period_end)->addDays(7)->toDateString(),
                    ])
                    ->whereNotNull('paid_amount')
                    ->first();
                if ($near) {
                    $paidAmount = (float) $near->paid_amount;
                    $diff = round($expectedAmount - $paidAmount, 4);
                }
            }

            Reconciliation::query()->updateOrCreate(
                ['tenant_id' => $payout->tenant_id, 'payout_id' => $payout->id],
                [
                    'matched_payment_reference' => $payout->payout_reference,
                    'matched_amount' => $paidAmount,
                    'matched_date' => $payout->paid_date,
                    'match_method' => $matchMethod,
                    'tolerance_used' => $ruleSet->toleranceAmount,
                    'notes' => $matchMethod === 'HEURISTIC'
                        ? 'Matched with heuristic fallback'
                        : 'Matched by payout reference',
                ]
            );

            if (abs($diff) <= $ruleSet->toleranceAmount) {
                $payout->status = 'PAID';
            } elseif ($paidAmount <= 0.0) {
                $payout->status = 'EXPECTED';
            } else {
                $payout->status = 'DISCREPANCY';
            }
            $payout->save();

            $this->detectAnomaliesAction->execute($payout->id);
        }
    }
}

