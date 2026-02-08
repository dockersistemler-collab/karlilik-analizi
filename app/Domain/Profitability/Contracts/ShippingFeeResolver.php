<?php

namespace App\Domain\Profitability\Contracts;

use App\Domain\Profitability\DTO\ProfitabilityInput;

/**
 * Resolves shipping fee for an order.
 */
interface ShippingFeeResolver
{
    public function resolveShippingFee(ProfitabilityInput $input, ?int $userId): string;
}
