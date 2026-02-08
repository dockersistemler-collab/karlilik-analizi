<?php

namespace App\Domain\Profitability\Calculators;

use App\Domain\Profitability\Contracts\CostComponentCalculator;
use App\Domain\Profitability\Contracts\ProductCostResolver;
use App\Domain\Profitability\DTO\ProfitabilityInput;

/**
 * Calculates product cost component.
 */
class ProductCostCalculator implements CostComponentCalculator
{
    public function __construct(private readonly ProductCostResolver $resolver)
    {
    }

    public function key(): string
    {
        return 'product_cost';
    }

    public function calculate(ProfitabilityInput $input): string
    {
        return $this->resolver->resolveProductCostFromOrderItems($input->items, $input->user_id);
    }
}
