<?php

namespace App\Domain\Profitability\Contracts;

use App\Domain\Profitability\DTO\ProfitabilityInput;

/**
 * Resolves refund shipping adjustment.
 */
interface RefundShippingResolver
{
    public function resolveRefundShippingAdjustment(ProfitabilityInput $input): string;
}
