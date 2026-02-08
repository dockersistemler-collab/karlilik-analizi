<?php

namespace App\Domain\Profitability;

use App\Domain\Profitability\Contracts\CostComponentCalculator;
use App\Domain\Profitability\DTO\ProfitabilityBreakdown;
use App\Domain\Profitability\DTO\ProfitabilityInput;
use App\Support\Decimal;

/**
 * Aggregates profitability components and computes totals.
 */
class ProfitabilityCalculator
{
    /**
     * @var array<int, CostComponentCalculator>
     */
    private array $calculators;

    /**
     * @param iterable<int, CostComponentCalculator> $calculators
     */
    public function __construct(iterable $calculators)
    {
        $this->calculators = is_array($calculators) ? $calculators : iterator_to_array($calculators);
    }

    public function calculate(ProfitabilityInput $input): ProfitabilityBreakdown
    {
        $components = [
            'product_cost' => '0',
            'commission_amount' => '0',
            'shipping_fee' => '0',
            'platform_service_fee' => '0',
            'refunds_shipping_adjustment' => '0',
            'sales_vat_amount' => '0',
        ];

        $vatRatePercent = '0';

        foreach ($this->calculators as $calculator) {
            $key = $calculator->key();
            $value = $calculator->calculate($input);
            $components[$key] = $value;

            if (method_exists($calculator, 'getVatRatePercent')) {
                $vatRatePercent = (string) $calculator->getVatRatePercent();
            }
        }

        $salePrice = Decimal::round($input->sale_price, Decimal::MONEY_SCALE);
        $productCost = Decimal::round($components['product_cost'], Decimal::MONEY_SCALE);
        $commission = Decimal::round($components['commission_amount'], Decimal::MONEY_SCALE);
        $shipping = Decimal::round($components['shipping_fee'], Decimal::MONEY_SCALE);
        $platformFee = Decimal::round($components['platform_service_fee'], Decimal::MONEY_SCALE);
        $refundAdjust = Decimal::round($components['refunds_shipping_adjustment'], Decimal::MONEY_SCALE);
        $salesVat = Decimal::round($components['sales_vat_amount'], Decimal::MONEY_SCALE);

        $withholdingRatePercent = '0';
        if (is_array($input->marketplace_data) && array_key_exists('withholding_tax_rate', $input->marketplace_data)) {
            $withholdingRatePercent = (string) $input->marketplace_data['withholding_tax_rate'];
        }
        $withholdingTax = '0';
        if (Decimal::cmp($withholdingRatePercent, '0', Decimal::CALC_SCALE) !== 0) {
            $ratio = Decimal::div($withholdingRatePercent, '100', Decimal::CALC_SCALE);
            $withholdingTax = Decimal::mul($salePrice, $ratio, Decimal::CALC_SCALE);
            $withholdingTax = Decimal::round($withholdingTax, Decimal::MONEY_SCALE);
        }

        $profit = Decimal::sub($salePrice, $productCost, Decimal::CALC_SCALE);
        $profit = Decimal::sub($profit, $commission, Decimal::CALC_SCALE);
        $profit = Decimal::sub($profit, $shipping, Decimal::CALC_SCALE);
        $profit = Decimal::sub($profit, $platformFee, Decimal::CALC_SCALE);
        $profit = Decimal::sub($profit, $withholdingTax, Decimal::CALC_SCALE);
        $profit = Decimal::add($profit, $refundAdjust, Decimal::CALC_SCALE);
        $profit = Decimal::round($profit, Decimal::MONEY_SCALE);

        $totalCosts = Decimal::add($productCost, $commission, Decimal::CALC_SCALE);
        $totalCosts = Decimal::add($totalCosts, $shipping, Decimal::CALC_SCALE);
        $totalCosts = Decimal::add($totalCosts, $platformFee, Decimal::CALC_SCALE);
        $totalCosts = Decimal::add($totalCosts, $withholdingTax, Decimal::CALC_SCALE);
        $totalCosts = Decimal::sub($totalCosts, $refundAdjust, Decimal::CALC_SCALE);
        $totalCosts = Decimal::round($totalCosts, Decimal::MONEY_SCALE);

        $percentScale = 8;
        $profitMargin = '0';
        if (Decimal::cmp($salePrice, '0', Decimal::CALC_SCALE) !== 0) {
            $profitMargin = Decimal::div($profit, $salePrice, $percentScale);
            $profitMargin = Decimal::mul($profitMargin, '100', $percentScale);
        }
        $profitMargin = Decimal::add($profitMargin, '0.000001', $percentScale);
        $profitMargin = number_format(round((float) $profitMargin, Decimal::MONEY_SCALE), Decimal::MONEY_SCALE, '.', '');

        $profitMarkup = '0';
        if (Decimal::cmp($totalCosts, '0', Decimal::CALC_SCALE) !== 0) {
            $profitMarkup = Decimal::div($profit, $totalCosts, $percentScale);
            $profitMarkup = Decimal::mul($profitMarkup, '100', $percentScale);
        }
        $profitMarkup = Decimal::add($profitMarkup, '0.000001', $percentScale);
        $profitMarkup = number_format(round((float) $profitMarkup, Decimal::MONEY_SCALE), Decimal::MONEY_SCALE, '.', '');

        return new ProfitabilityBreakdown([
            'sale_price' => $salePrice,
            'product_cost' => $productCost,
            'commission_amount' => $commission,
            'shipping_fee' => $shipping,
            'platform_service_fee' => $platformFee,
            'refunds_shipping_adjustment' => $refundAdjust,
            'withholding_tax_amount' => $withholdingTax,
            'profit_amount' => $profit,
            'profit_margin_percent' => $profitMargin,
            'profit_markup_percent' => $profitMarkup,
            'sales_vat_amount' => $salesVat,
            'vat_rate_percent' => Decimal::round($vatRatePercent, Decimal::MONEY_SCALE),
        ]);
    }
}
