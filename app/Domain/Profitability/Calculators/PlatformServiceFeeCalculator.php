<?php

namespace App\Domain\Profitability\Calculators;

use App\Domain\Profitability\Contracts\CostComponentCalculator;
use App\Domain\Profitability\DTO\ProfitabilityInput;
use App\Support\Decimal;

/**
 * Returns platform service fee component.
 */
class PlatformServiceFeeCalculator implements CostComponentCalculator
{
    public function key(): string
    {
        return 'platform_service_fee';
    }

    public function calculate(ProfitabilityInput $input): string
    {
        $fee = config('marketplace.platform_service_fee', 0);
        $fee = is_numeric($fee) ? (string) $fee : '0';
        return Decimal::round($fee, Decimal::MONEY_SCALE);
    }
}
