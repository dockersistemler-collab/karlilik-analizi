<?php

namespace App\Services\ProfitEngine;

use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\OrderItem;
use App\Models\Order;
use App\Models\OrderProfitSnapshot;
use App\Models\Product;
use App\Models\ProfitCostProfile;

class ProfitCalculator
{
    public const CALCULATION_VERSION = 'v1';

    public function __construct(
        private readonly FeeRuleResolver $feeRuleResolver
    ) {
    }

    public function calculateAndStore(Order $order): OrderProfitSnapshot
    {
        $order->loadMissing('orderItems', 'marketplace');

        $tenantId = (int) ($order->tenant_id ?: $order->user_id);
        $userId = (int) $order->user_id;
        $marketplace = $this->resolveMarketplace($order);

        $profile = ProfitCostProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderByDesc('is_default')
            ->latest('id')
            ->first();

        $grossRevenue = 0.0;
        $productCost = 0.0;
        $commissionAmount = 0.0;
        $shippingAmount = 0.0;
        $serviceAmount = 0.0;
        $campaignAmount = 0.0;
        $missingCostSkus = [];
        $ruleMissing = false;
        $itemBreakdowns = [];

        foreach ($order->orderItems as $item) {
            $row = $this->calculateItem(
                $item,
                $tenantId,
                $userId,
                $marketplace
            );

            $grossRevenue += $row['gross_revenue'];
            $productCost += $row['product_cost'];
            $commissionAmount += $row['commission_amount'];
            $shippingAmount += $row['shipping_amount'];
            $serviceAmount += $row['service_amount'];
            $campaignAmount += $row['campaign_amount'];
            $ruleMissing = $ruleMissing || $row['rule_missing'];

            if ($row['cost_missing']) {
                $missingCostSkus[] = (string) $item->sku;
            }

            $itemBreakdowns[] = $row['meta'];
        }

        if ($grossRevenue <= 0) {
            $grossRevenue = (float) ($order->total_amount ?? 0);
        }

        $packagingAmount = (float) ($profile?->packaging_cost ?? 0);
        $operationalAmount = (float) ($profile?->operational_cost ?? 0);
        $adAmount = (float) ($profile?->ad_cost_default ?? 0);
        $returnRateDefault = (float) ($profile?->return_rate_default ?? 0);
        $returnRiskAmount = $grossRevenue * ($returnRateDefault / 100);
        $otherCostAmount = 0.0;

        $totalCosts = $productCost
            + $commissionAmount
            + $shippingAmount
            + $serviceAmount
            + $campaignAmount
            + $adAmount
            + $packagingAmount
            + $operationalAmount
            + $returnRiskAmount
            + $otherCostAmount;

        $netProfit = $grossRevenue - $totalCosts;
        $netMargin = $grossRevenue > 0 ? ($netProfit / $grossRevenue) * 100 : 0.0;

        return OrderProfitSnapshot::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'marketplace' => $marketplace,
                'gross_revenue' => $grossRevenue,
                'product_cost' => $productCost,
                'commission_amount' => $commissionAmount,
                'shipping_amount' => $shippingAmount,
                'service_amount' => $serviceAmount,
                'campaign_amount' => $campaignAmount,
                'ad_amount' => $adAmount,
                'packaging_amount' => $packagingAmount,
                'operational_amount' => $operationalAmount,
                'return_risk_amount' => $returnRiskAmount,
                'other_cost_amount' => $otherCostAmount,
                'net_profit' => $netProfit,
                'net_margin' => $netMargin,
                'calculation_version' => self::CALCULATION_VERSION,
                'calculated_at' => now(),
                'meta' => [
                    'rule_missing' => $ruleMissing,
                    'cost_missing_skus' => array_values(array_unique($missingCostSkus)),
                    'item_breakdowns' => $itemBreakdowns,
                ],
            ]
        );
    }

    private function calculateItem(
        OrderItem $item,
        int $tenantId,
        int $userId,
        string $marketplace
    ): array {
        $qty = max(1, (int) $item->qty);
        $grossRevenue = (float) $item->sale_price * $qty;
        $declaredCostPrice = (float) $item->cost_price;

        $sku = $item->sku ? (string) $item->sku : null;
        $raw = is_array($item->raw_payload) ? $item->raw_payload : [];
        $categoryId = isset($raw['category_id']) ? (int) $raw['category_id'] : null;
        $brandId = isset($raw['brand_id']) ? (int) $raw['brand_id'] : null;

        $rule = $this->feeRuleResolver->resolve(
            $tenantId,
            $userId,
            $marketplace,
            $sku,
            $categoryId,
            $brandId
        );

        $resolvedCostPrice = $declaredCostPrice;
        if ($resolvedCostPrice <= 0 && $sku !== null) {
            $resolvedCostPrice = (float) (Product::query()
                ->where('user_id', $userId)
                ->where('sku', $sku)
                ->value('cost_price') ?? 0);
        }

        $costMissing = $resolvedCostPrice <= 0;
        $productCost = $resolvedCostPrice * $qty;

        if ($rule) {
            $commissionAmount = $grossRevenue * ((float) $rule->commission_rate / 100) + ((float) $rule->fixed_fee * $qty);
            $shippingAmount = (float) $rule->shipping_fee * $qty;
            $serviceAmount = (float) $rule->service_fee * $qty;
            $campaignAmount = $grossRevenue * ((float) $rule->campaign_contribution_rate / 100);
        } else {
            $commissionAmount = max(0.0, (float) $item->commission_amount);
            $shippingAmount = max(0.0, (float) $item->shipping_amount);
            $serviceAmount = max(0.0, (float) $item->service_fee_amount);
            $campaignAmount = 0.0;
        }

        return [
            'gross_revenue' => $grossRevenue,
            'product_cost' => $productCost,
            'commission_amount' => $commissionAmount,
            'shipping_amount' => $shippingAmount,
            'service_amount' => $serviceAmount,
            'campaign_amount' => $campaignAmount,
            'rule_missing' => $rule === null,
            'cost_missing' => $costMissing,
            'meta' => [
                'order_item_id' => $item->id,
                'sku' => $item->sku,
                'qty' => $qty,
                'rule_id' => $rule?->id,
                'rule_missing' => $rule === null,
                'cost_missing' => $costMissing,
            ],
        ];
    }

    private function resolveMarketplace(Order $order): string
    {
        if ($order->marketplace && is_string($order->marketplace->code) && $order->marketplace->code !== '') {
            return strtolower($order->marketplace->code);
        }

        if (!empty($order->marketplace_integration_id)) {
            $code = MarketplaceIntegration::query()
                ->withoutGlobalScope('tenant_scope')
                ->where('id', $order->marketplace_integration_id)
                ->value('code');
            if (is_string($code) && $code !== '') {
                return strtolower($code);
            }
        }

        $rawPayload = is_array($order->raw_payload) ? $order->raw_payload : [];
        $rawMarketplace = $rawPayload['marketplace'] ?? data_get($order->marketplace_data, 'code');
        if (is_string($rawMarketplace) && $rawMarketplace !== '') {
            return strtolower($rawMarketplace);
        }

        return 'unknown';
    }
}

