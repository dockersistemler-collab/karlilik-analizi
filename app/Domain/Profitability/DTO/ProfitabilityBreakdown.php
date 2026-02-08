<?php

namespace App\Domain\Profitability\DTO;

/**
 * Profitability breakdown output.
 */
class ProfitabilityBreakdown
{
    public string $sale_price;
    public string $product_cost;
    public string $commission_amount;
    public string $shipping_fee;
    public string $platform_service_fee;
    public string $refunds_shipping_adjustment;
    public string $withholding_tax_amount;
    public string $profit_amount;
    public string $profit_margin_percent;
    public string $profit_markup_percent;
    public string $sales_vat_amount;
    public string $vat_rate_percent;

    public function __construct(array $data)
    {
        $this->sale_price = $data['sale_price'];
        $this->product_cost = $data['product_cost'];
        $this->commission_amount = $data['commission_amount'];
        $this->shipping_fee = $data['shipping_fee'];
        $this->platform_service_fee = $data['platform_service_fee'];
        $this->refunds_shipping_adjustment = $data['refunds_shipping_adjustment'];
        $this->withholding_tax_amount = $data['withholding_tax_amount'];
        $this->profit_amount = $data['profit_amount'];
        $this->profit_margin_percent = $data['profit_margin_percent'];
        $this->profit_markup_percent = $data['profit_markup_percent'];
        $this->sales_vat_amount = $data['sales_vat_amount'];
        $this->vat_rate_percent = $data['vat_rate_percent'];
    }
}
