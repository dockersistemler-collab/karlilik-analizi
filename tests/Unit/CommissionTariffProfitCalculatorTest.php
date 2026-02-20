<?php

namespace Tests\Unit;

use App\Services\CommissionTariffs\ProfitCalculator;
use PHPUnit\Framework\TestCase;

class CommissionTariffProfitCalculatorTest extends TestCase
{
    public function test_calculates_profit_and_net_vat(): void
    {
        $calc = new ProfitCalculator();

        $result = $calc->calculate([
            'salePrice' => 1000,
            'productCost' => 600,
            'productVatRate' => 20,
            'commissionPercent' => 10,
            'shippingFee' => 30,
            'platformServiceFee' => 20,
            'commissionVatRate' => 20,
            'shippingVatRate' => 20,
            'serviceVatRate' => 20,
        ]);

        $this->assertEquals(100, $result['commission_amount']);
        $this->assertEquals(20, $result['commission_vat']);
        $this->assertEquals(200, $result['sale_vat']);
        $this->assertEquals(120, $result['cost_vat']);
        $this->assertEquals(6, $result['shipping_vat']);
        $this->assertEquals(4, $result['service_vat']);

        $this->assertEquals(50, $result['net_vat']);
        $this->assertEquals(200, $result['profit']);
        $this->assertEquals(20, $result['profit_rate']);
    }
}
