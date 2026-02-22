<?php

namespace Tests\Unit;

use App\Domains\Settlements\Rules\RuleEvaluator;
use PHPUnit\Framework\TestCase;

class SettlementRuleEvaluatorTest extends TestCase
{
    public function test_it_computes_net_vat_and_profit(): void
    {
        $evaluator = new RuleEvaluator();

        $result = $evaluator->computeBreakdown([
            'sale_price' => 600,
            'sale_vat' => 100,
            'cost_price' => 350,
            'cost_vat' => 58.33,
            'commission_amount' => 60,
            'commission_vat' => 10,
            'shipping_amount' => 25,
            'shipping_vat' => 4.17,
            'service_fee_amount' => 10,
            'service_fee_vat' => 1.67,
        ]);

        $this->assertEquals(25.83, $result['net_vat']);
        $this->assertEquals(129.17, $result['profit']);
    }
}

