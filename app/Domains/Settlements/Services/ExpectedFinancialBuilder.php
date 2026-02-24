<?php

namespace App\Domains\Settlements\Services;

use App\Domains\Settlements\Models\OrderFinancialItem;
use App\Domains\Settlements\Models\ReconciliationRule;
use App\Models\Order;

class ExpectedFinancialBuilder
{
    public function buildForOrder(Order $order, string $marketplace = 'trendyol'): void
    {
        $order->financialItems()->delete();

        $totals = is_array($order->totals) ? $order->totals : [];
        $currency = (string) ($order->currency ?: 'TRY');

        $saleGross = round((float) ($order->total_amount ?? 0), 2);
        $saleVat = round((float) ($totals['vat_total'] ?? 0), 2);
        $saleNet = round($saleGross - $saleVat, 2);

        $commissionGross = round((float) ($order->commission_amount ?? 0), 2);
        $commissionVat = round((float) ($totals['commission_vat'] ?? ($commissionGross * 0.20)), 2);
        $commissionNet = round($commissionGross - $commissionVat, 2);

        $shippingGross = round((float) ($totals['shipping_total'] ?? 0), 2);
        if ($shippingGross <= 0) {
            $shippingGross = $this->resolveShippingByRule($marketplace, $order);
        }
        $shippingVat = round((float) ($totals['shipping_vat'] ?? ($shippingGross * 0.20)), 2);
        $shippingNet = round($shippingGross - $shippingVat, 2);

        $serviceFeeGross = round((float) ($totals['service_fee_total'] ?? $this->resolveServiceFeeByRule($marketplace)), 2);
        $serviceFeeVat = round((float) ($totals['service_fee_vat'] ?? ($serviceFeeGross * 0.20)), 2);
        $serviceFeeNet = round($serviceFeeGross - $serviceFeeVat, 2);

        $this->createItem($order, $marketplace, 'sale', $saleGross, $saleVat, $saleNet, $currency, 'api', $totals);
        $this->createItem($order, $marketplace, 'commission', $commissionGross, $commissionVat, $commissionNet, $currency, 'api', $totals);
        $this->createItem($order, $marketplace, 'shipping', $shippingGross, $shippingVat, $shippingNet, $currency, $shippingGross > 0 ? 'api' : 'rule', $totals);
        $this->createItem($order, $marketplace, 'service_fee', $serviceFeeGross, $serviceFeeVat, $serviceFeeNet, $currency, 'rule', $totals);
    }

    private function resolveShippingByRule(string $marketplace, Order $order): float
    {
        $rule = ReconciliationRule::query()
            ->where('marketplace', $marketplace)
            ->where('rule_type', 'loss_rule')
            ->where('key', 'SHIPPING_DEFAULT')
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->first();

        $value = (float) data_get($rule?->value, 'amount', 0);
        if ($value > 0) {
            return round($value, 2);
        }

        $items = is_array($order->items) ? $order->items : [];
        $desi = (float) collect($items)->sum(fn ($item) => (float) data_get($item, 'desi', 0));

        return round(max(0, $desi * 4.00), 2);
    }

    private function resolveServiceFeeByRule(string $marketplace): float
    {
        $rule = ReconciliationRule::query()
            ->where('marketplace', $marketplace)
            ->where('rule_type', 'loss_rule')
            ->where('key', 'SERVICE_FEE_DEFAULT')
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->first();

        return round((float) data_get($rule?->value, 'amount', 0), 2);
    }

    /**
     * @param  array<string,mixed>  $rawRef
     */
    private function createItem(
        Order $order,
        string $marketplace,
        string $type,
        float $gross,
        float $vat,
        float $net,
        string $currency,
        string $source,
        array $rawRef
    ): void {
        OrderFinancialItem::query()->create([
            'order_id' => $order->id,
            'marketplace' => $marketplace,
            'type' => $type,
            'gross_amount' => round($gross, 2),
            'vat_amount' => round($vat, 2),
            'net_amount' => round($net, 2),
            'currency' => $currency,
            'source' => $source,
            'raw_ref' => $rawRef,
        ]);
    }
}

