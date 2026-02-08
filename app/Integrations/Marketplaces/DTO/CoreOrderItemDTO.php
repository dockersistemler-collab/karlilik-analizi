<?php

namespace App\Integrations\Marketplaces\DTO;

use Carbon\CarbonImmutable;

class CoreOrderItemDTO
{
    public function __construct(
        public string $marketplace,
        public string $orderId,
        public string $orderItemId,
        public CarbonImmutable $orderDate,
        public ?CarbonImmutable $shipDate,
        public ?CarbonImmutable $deliveredDate,
        public ?string $sku,
        public ?int $productId,
        public ?string $variant,
        public int $quantity,
        public string $currency,
        public float $fxRate,
        public float $grossSales,
        public float $discounts,
        public float $refunds,
        public float $commissionFee,
        public float $paymentFee,
        public float $shippingFee,
        public float $otherFees,
        public ?float $vatAmount,
        public ?float $taxAmount,
        public ?float $cogsUnit,
        public string $status
    ) {
    }

    public function toArray(): array
    {
        return [
            'marketplace' => $this->marketplace,
            'order_id' => $this->orderId,
            'order_item_id' => $this->orderItemId,
            'order_date' => $this->orderDate->toDateTimeString(),
            'ship_date' => $this->shipDate?->toDateTimeString(),
            'delivered_date' => $this->deliveredDate?->toDateTimeString(),
            'sku' => $this->sku,
            'product_id' => $this->productId,
            'variant' => $this->variant,
            'quantity' => $this->quantity,
            'currency' => $this->currency,
            'fx_rate' => $this->fxRate,
            'gross_sales' => $this->grossSales,
            'discounts' => $this->discounts,
            'refunds' => $this->refunds,
            'commission_fee' => $this->commissionFee,
            'payment_fee' => $this->paymentFee,
            'shipping_fee' => $this->shippingFee,
            'other_fees' => $this->otherFees,
            'vat_amount' => $this->vatAmount,
            'tax_amount' => $this->taxAmount,
            'cogs_unit' => $this->cogsUnit,
            'status' => $this->status,
        ];
    }
}
