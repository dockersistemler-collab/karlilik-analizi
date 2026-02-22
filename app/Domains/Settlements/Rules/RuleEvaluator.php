<?php

namespace App\Domains\Settlements\Rules;

class RuleEvaluator
{
    public function computeBreakdown(array $item): array
    {
        $salePrice = (float) ($item['sale_price'] ?? 0);
        $costPrice = (float) ($item['cost_price'] ?? 0);
        $commissionAmount = (float) ($item['commission_amount'] ?? 0);
        $shippingAmount = (float) ($item['shipping_amount'] ?? 0);
        $serviceFeeAmount = (float) ($item['service_fee_amount'] ?? 0);

        $saleVat = (float) ($item['sale_vat'] ?? 0);
        $costVat = (float) ($item['cost_vat'] ?? 0);
        $commissionVat = (float) ($item['commission_vat'] ?? 0);
        $shippingVat = (float) ($item['shipping_vat'] ?? 0);
        $serviceFeeVat = (float) ($item['service_fee_vat'] ?? 0);

        $netKdv = $saleVat - $costVat - $commissionVat - $shippingVat - $serviceFeeVat;
        $profit = $salePrice - $costPrice - $commissionAmount - $shippingAmount - $serviceFeeAmount - $netKdv;

        return [
            'net_vat' => round($netKdv, 4),
            'profit' => round($profit, 4),
        ];
    }
}

