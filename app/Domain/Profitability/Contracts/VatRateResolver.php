<?php

namespace App\Domain\Profitability\Contracts;

use App\Domain\Profitability\DTO\ProfitabilityInput;

/**
 * Resolves VAT rate percentage.
 */
interface VatRateResolver
{
    public function resolveVatRatePercent(ProfitabilityInput $input): string;
}
