<?php

namespace App\Domain\Profitability\Calculators;

use App\Domain\Profitability\Contracts\CostComponentCalculator;
use App\Domain\Profitability\Contracts\RefundShippingResolver;
use App\Domain\Profitability\DTO\ProfitabilityInput;

/**
 * Calculates refund shipping adjustment component.
 */
class RefundShippingAdjustmentCalculator implements CostComponentCalculator
{
    public function __construct(private readonly RefundShippingResolver $resolver)
    {
    }

    public function key(): string
    {
        return 'refunds_shipping_adjustment';
    }

    public function calculate(ProfitabilityInput $input): string
    {
        return $this->resolver->resolveRefundShippingAdjustment($input);
    }
}
