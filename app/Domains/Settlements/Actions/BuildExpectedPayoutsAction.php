<?php

namespace App\Domains\Settlements\Actions;

use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\OrderItem;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\PayoutTransaction;
use App\Domains\Settlements\Models\SettlementRule;
use App\Domains\Settlements\Rules\SettlementRuleSet;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use Carbon\Carbon;

class BuildExpectedPayoutsAction
{
    public function execute(int $accountId, string $periodStart, string $periodEnd): Payout
    {
        $account = MarketplaceAccount::query()->findOrFail($accountId);
        $integration = MarketplaceIntegration::query()
            ->where('code', strtolower((string) ($account->connector_key ?: $account->marketplace)))
            ->firstOrFail();

        $ruleRow = SettlementRule::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $account->tenant_id)
            ->where('marketplace_integration_id', $integration->id)
            ->first();

        $ruleSet = SettlementRuleSet::fromArray($ruleRow?->ruleset ?? []);

        $orderIds = Order::query()
            ->where('tenant_id', $account->tenant_id)
            ->where('marketplace_account_id', $account->id)
            ->whereBetween('order_date', [$periodStart . ' 00:00:00', $periodEnd . ' 23:59:59'])
            ->pluck('id');

        $items = OrderItem::query()
            ->where('tenant_id', $account->tenant_id)
            ->whereIn('order_id', $orderIds)
            ->get();

        $expectedAmount = (float) $items->sum(fn (OrderItem $item) => (float) data_get($item->calculated, 'profit', 0));

        $expectedDate = $this->computeExpectedDate($ruleSet, $periodEnd);

        $payout = Payout::query()->updateOrCreate(
            [
                'tenant_id' => $account->tenant_id,
                'marketplace_account_id' => $account->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
            [
                'marketplace_integration_id' => $integration->id,
                'expected_date' => $expectedDate,
                'expected_amount' => round($expectedAmount, 4),
                'currency' => 'TRY',
                'status' => 'EXPECTED',
                'totals' => [
                    'orders' => $items->count(),
                    'net_profit_sum' => round($expectedAmount, 4),
                ],
            ]
        );

        PayoutTransaction::query()->where('payout_id', $payout->id)->delete();

        foreach ($items as $item) {
            PayoutTransaction::query()->create([
                'tenant_id' => $account->tenant_id,
                'payout_id' => $payout->id,
                'type' => 'ORDER',
                'reference_id' => $item->order_id,
                'amount' => (float) data_get($item->calculated, 'profit', 0),
                'vat_amount' => (float) data_get($item->calculated, 'net_vat', 0),
                'meta' => [
                    'sku' => $item->sku,
                    'variant_id' => $item->variant_id,
                ],
            ]);
        }

        return $payout->fresh(['transactions']);
    }

    private function computeExpectedDate(SettlementRuleSet $ruleSet, string $periodEnd): string
    {
        $end = Carbon::parse($periodEnd);

        return match ($ruleSet->cycleType) {
            'WEEKLY' => $end->next(Carbon::MONDAY)->toDateString(),
            'BIWEEKLY' => $end->addDays(14)->toDateString(),
            'MONTHLY' => $end->endOfMonth()->toDateString(),
            default => $end->addDays($ruleSet->cycleDays)->toDateString(),
        };
    }
}

