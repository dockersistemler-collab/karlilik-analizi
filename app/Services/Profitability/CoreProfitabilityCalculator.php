<?php

namespace App\Services\Profitability;

use App\Models\CoreOrderItem;

class CoreProfitabilityCalculator
{
    public function recalculate(CoreOrderItem $item): CoreOrderItem
    {
        $grossSales = (float) ($item->gross_sales ?? 0);
        $discounts = (float) ($item->discounts ?? 0);
        $refunds = (float) ($item->refunds ?? 0);

        $item->net_sales = $grossSales - $discounts - $refunds;

        $item->fees_total = (float) ($item->commission_fee ?? 0)
            + (float) ($item->payment_fee ?? 0)
            + (float) ($item->shipping_fee ?? 0)
            + (float) ($item->other_fees ?? 0);

        if ($item->cogs_total === null && $item->cogs_unit !== null) {
            $item->cogs_total = (float) $item->cogs_unit * (int) ($item->quantity ?? 1);
        }

        $cogs = (float) ($item->cogs_total ?? 0);
        $item->gross_profit = $item->net_sales - $cogs;
        $item->contribution_margin = $item->net_sales - ($cogs + (float) $item->fees_total);

        return $item;
    }
}
