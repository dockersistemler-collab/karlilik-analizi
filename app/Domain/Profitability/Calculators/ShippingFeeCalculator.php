<?php

namespace App\Domain\Profitability\Calculators;

use App\Domain\Profitability\Contracts\CostComponentCalculator;
use App\Domain\Profitability\Contracts\ShippingFeeResolver;
use App\Domain\Profitability\DTO\ProfitabilityInput;

/**
 * Calculates shipping fee component.
 */
class ShippingFeeCalculator implements CostComponentCalculator
{
    public function __construct(private readonly ShippingFeeResolver $resolver)
    {
    }

    public function key(): string
    {
        return 'shipping_fee';
    }

    public function calculate(ProfitabilityInput $input): string
    {
        return $this->resolver->resolveShippingFee($input, $input->user_id);
    }
}
