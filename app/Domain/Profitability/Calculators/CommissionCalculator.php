<?php

namespace App\Domain\Profitability\Calculators;

use App\Domain\Profitability\Contracts\CostComponentCalculator;
use App\Domain\Profitability\DTO\ProfitabilityInput;
use App\Support\Decimal;

/**
 * Returns commission amount.
 */
class CommissionCalculator implements CostComponentCalculator
{
    public function key(): string
    {
        return 'commission_amount';
    }

    public function calculate(ProfitabilityInput $input): string
    {
        return Decimal::round($input->commission_amount, Decimal::MONEY_SCALE);
    }
}
