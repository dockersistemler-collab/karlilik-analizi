<?php

namespace App\Services\CommissionTariffs;

class ProfitCalculator
{
    public function calculate(array $input): array
    {
        $salePrice = (float) ($input['salePrice'] ?? 0);
        $productCost = (float) ($input['productCost'] ?? 0);
        $productVatRate = (float) ($input['productVatRate'] ?? 0);
        $commissionPercent = (float) ($input['commissionPercent'] ?? 0);
        $shippingFee = (float) ($input['shippingFee'] ?? 0);
        $platformServiceFee = (float) ($input['platformServiceFee'] ?? 0);
        $commissionVatRate = (float) ($input['commissionVatRate'] ?? 0);
        $shippingVatRate = (float) ($input['shippingVatRate'] ?? 0);
        $serviceVatRate = (float) ($input['serviceVatRate'] ?? 0);

        $commissionAmount = $salePrice * $commissionPercent / 100;
        $saleVat = $salePrice * $productVatRate / 100;
        $costVat = $productCost * $productVatRate / 100;
        $commissionVat = $commissionAmount * $commissionVatRate / 100;
        $shippingVat = $shippingFee * $shippingVatRate / 100;
        $serviceVat = $platformServiceFee * $serviceVatRate / 100;

        $netVat = $saleVat - $costVat - $commissionVat - $shippingVat - $serviceVat;
        $profit = $salePrice - $productCost - $commissionAmount - $shippingFee - $platformServiceFee - $netVat;
        $profitRate = $salePrice > 0 ? ($profit / $salePrice) * 100 : 0;

        return [
            'commission_amount' => round($commissionAmount, 2),
            'commission_vat' => round($commissionVat, 2),
            'sale_vat' => round($saleVat, 2),
            'cost_vat' => round($costVat, 2),
            'shipping_vat' => round($shippingVat, 2),
            'service_vat' => round($serviceVat, 2),
            'net_vat' => round($netVat, 2),
            'profit' => round($profit, 2),
            'profit_rate' => round($profitRate, 2),
        ];
    }
}
