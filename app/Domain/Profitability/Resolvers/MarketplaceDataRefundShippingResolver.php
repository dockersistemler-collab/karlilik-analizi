<?php

namespace App\Domain\Profitability\Resolvers;

use App\Domain\Profitability\Contracts\RefundShippingResolver;
use App\Domain\Profitability\DTO\ProfitabilityInput;
use App\Support\Decimal;

/**
 * Resolves refund shipping adjustment from marketplace data.
 */
class MarketplaceDataRefundShippingResolver implements RefundShippingResolver
{
    public function resolveRefundShippingAdjustment(ProfitabilityInput $input): string
    {
        $data = $input->marketplace_data;
        $out = data_get($data, 'refund_out_shipping_fee', 0);
        $ret = data_get($data, 'refund_return_shipping_fee', 0);

        $out = is_numeric($out) ? (string) $out : '0';
        $ret = is_numeric($ret) ? (string) $ret : '0';

        $sum = Decimal::add($out, $ret, Decimal::CALC_SCALE);
        $adjustment = Decimal::mul($sum, '-1', Decimal::CALC_SCALE);

        return Decimal::round($adjustment, Decimal::MONEY_SCALE);
    }
}
