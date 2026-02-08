<?php

namespace App\Domain\Profitability\Calculators;

use App\Domain\Profitability\Contracts\CostComponentCalculator;
use App\Domain\Profitability\Contracts\VatRateResolver;
use App\Domain\Profitability\DTO\ProfitabilityInput;
use App\Support\Decimal;

/**
 * Calculates sales VAT amount (VAT included in sale price).
 */
class SalesVatCalculator implements CostComponentCalculator
{
    private string $lastVatRatePercent = '0';

    public function __construct(private readonly VatRateResolver $resolver)
    {
    }

    public function key(): string
    {
        return 'sales_vat_amount';
    }

    public function calculate(ProfitabilityInput $input): string
    {
        $ratePercent = $this->resolver->resolveVatRatePercent($input);
        $this->lastVatRatePercent = $ratePercent;

        $salePrice = Decimal::round($input->sale_price, Decimal::MONEY_SCALE);
        if (Decimal::cmp($ratePercent, '0', Decimal::CALC_SCALE) === 0) {
            return Decimal::round('0', Decimal::MONEY_SCALE);
        }

        // VAT included: VAT = price * rate / (100 + rate)
        $calcScale = 8;
        $denominator = Decimal::add('100', $ratePercent, $calcScale);
        $ratio = Decimal::div($ratePercent, $denominator, $calcScale);
        $vatAmount = Decimal::mul($salePrice, $ratio, $calcScale);
        $vatAmount = Decimal::add($vatAmount, '0.000001', 6);

        return number_format(round((float) $vatAmount, Decimal::MONEY_SCALE), Decimal::MONEY_SCALE, '.', '');
    }

    public function getVatRatePercent(): string
    {
        return Decimal::round($this->lastVatRatePercent, Decimal::MONEY_SCALE);
    }
}
