<?php

namespace App\Domain\Profitability\Contracts;

use App\Domain\Profitability\DTO\ProfitabilityInput;

/**
 * Calculates a profitability component.
 */
interface CostComponentCalculator
{
    public function key(): string;

    public function calculate(ProfitabilityInput $input): string;
}
