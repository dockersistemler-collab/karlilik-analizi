<?php

namespace Tests\Unit;

use App\Integrations\Marketplaces\N11Adapter;
use App\Integrations\Marketplaces\TrendyolAdapter;
use App\Integrations\Marketplaces\HepsiburadaAdapter;
use App\Integrations\Marketplaces\AmazonAdapter;
use Tests\TestCase;

class MarketplaceAdaptersMappingTest extends TestCase
{
    public function test_trendyol_order_mapping_supports_nested_fallback_fields(): void
    {
        $adapter = new TrendyolAdapter();

        $dto = $adapter->mapOrderItemToCore([
            'orderNumber' => 'TY-ORDER-1',
            'lineId' => 'TY-LINE-9',
            'createdDate' => 1735689600000,
            'shipmentDate' => 1735776000000,
            'deliveryDate' => 1735862400000,
            'merchantSku' => 'SKU-TY-1',
            'quantity' => '2',
            'currencyCode' => 'TRY',
            'totalPrice' => '125.50',
            'discountAmount' => '5.25',
            'commissionAmount' => '12.40',
            'status' => 'shipped',
        ]);

        $this->assertSame('TY-ORDER-1', $dto->orderId);
        $this->assertSame('TY-LINE-9', $dto->orderItemId);
        $this->assertSame('SKU-TY-1', $dto->sku);
        $this->assertSame(2, $dto->quantity);
        $this->assertSame('TRY', $dto->currency);
        $this->assertSame(125.50, $dto->grossSales);
        $this->assertSame(5.25, $dto->discounts);
        $this->assertSame(12.40, $dto->commissionFee);
        $this->assertSame('shipped', $dto->status);
        $this->assertSame('2025-01-01 00:00:00', $dto->orderDate->utc()->toDateTimeString());
        $this->assertSame('2025-01-02 00:00:00', $dto->shipDate?->utc()->toDateTimeString());
        $this->assertSame('2025-01-03 00:00:00', $dto->deliveredDate?->utc()->toDateTimeString());
    }

    public function test_trendyol_return_and_fee_mapping_supports_fallback_fields(): void
    {
        $adapter = new TrendyolAdapter();

        $return = $adapter->mapReturnToCoreAdjustments([
            'lineItemId' => 'TY-LINE-20',
            'refundAmount' => '45.90',
            'currencyCode' => 'TRY',
            'createdDate' => 1735862400000,
        ]);

        $fee = $adapter->mapFeeToCoreAdjustments([
            'lineItemId' => 'TY-LINE-20',
            'feeType' => 'commission',
            'feeAmount' => '10.15',
            'currencyCode' => 'TRY',
            'transactionDate' => 1735948800000,
        ]);

        $this->assertSame('TY-LINE-20', $return->orderItemId);
        $this->assertSame(45.90, $return->amount);
        $this->assertSame('TRY', $return->currency);
        $this->assertSame('2025-01-03 00:00:00', $return->occurredAt->utc()->toDateTimeString());

        $this->assertSame('TY-LINE-20', $fee->orderItemId);
        $this->assertSame('commission', $fee->type);
        $this->assertSame(10.15, $fee->amount);
        $this->assertSame('TRY', $fee->currency);
        $this->assertSame('2025-01-04 00:00:00', $fee->occurredAt->utc()->toDateTimeString());
    }

    public function test_n11_mapping_supports_fallback_fields_and_numeric_dates(): void
    {
        $adapter = new N11Adapter();

        $order = $adapter->mapOrderItemToCore([
            'orderNumber' => 'N11-ORDER-3',
            'lineId' => 'N11-LINE-3',
            'createdAt' => '1736035200',
            'shipDate' => '2025-01-06 10:30:00',
            'sellerSku' => 'SKU-N11-3',
            'qty' => 3,
            'currencyCode' => 'TRY',
            'amount' => '399.90',
            'discount' => '9.90',
            'paymentAmount' => '4.20',
            'orderStatus' => 'delivered',
        ]);

        $return = $adapter->mapReturnToCoreAdjustments([
            'orderItemId' => 'N11-LINE-3',
            'refund_amount' => '99.00',
            'currencyCode' => 'TRY',
            'occurredAt' => '2025-01-07T08:00:00+03:00',
        ]);

        $fee = $adapter->mapFeeToCoreAdjustments([
            'orderItemId' => 'N11-LINE-3',
            'type' => 'payment',
            'value' => '2.50',
            'currency' => 'TRY',
            'createdAt' => 1736294400000,
        ]);

        $this->assertSame('N11-ORDER-3', $order->orderId);
        $this->assertSame('N11-LINE-3', $order->orderItemId);
        $this->assertSame('SKU-N11-3', $order->sku);
        $this->assertSame(3, $order->quantity);
        $this->assertSame('TRY', $order->currency);
        $this->assertSame(399.90, $order->grossSales);
        $this->assertSame(9.90, $order->discounts);
        $this->assertSame(4.20, $order->paymentFee);
        $this->assertSame('delivered', $order->status);
        $this->assertSame('2025-01-05 00:00:00', $order->orderDate->utc()->toDateTimeString());

        $this->assertSame('N11-LINE-3', $return->orderItemId);
        $this->assertSame(99.0, $return->amount);
        $this->assertSame('TRY', $return->currency);
        $this->assertSame('2025-01-07 05:00:00', $return->occurredAt->utc()->toDateTimeString());

        $this->assertSame('N11-LINE-3', $fee->orderItemId);
        $this->assertSame('payment', $fee->type);
        $this->assertSame(2.5, $fee->amount);
        $this->assertSame('TRY', $fee->currency);
        $this->assertSame('2025-01-08 00:00:00', $fee->occurredAt->utc()->toDateTimeString());
    }

    public function test_hepsiburada_mapping_supports_fallback_fields(): void
    {
        $adapter = new HepsiburadaAdapter();

        $order = $adapter->mapOrderItemToCore([
            'orderNumber' => 'HB-ORDER-12',
            'lineId' => 'HB-LINE-12',
            'createdAt' => 1736121600000,
            'merchantSku' => 'SKU-HB-12',
            'qty' => '2',
            'currencyCode' => 'TRY',
            'amount' => '250.75',
            'discountAmount' => '8.25',
            'commissionAmount' => '16.80',
            'orderStatus' => 'shipped',
        ]);

        $return = $adapter->mapReturnToCoreAdjustments([
            'lineItemId' => 'HB-LINE-12',
            'refundAmount' => '80.00',
            'currencyCode' => 'TRY',
            'createdDate' => 1736208000000,
        ]);

        $fee = $adapter->mapFeeToCoreAdjustments([
            'lineItemId' => 'HB-LINE-12',
            'feeType' => 'shipping',
            'feeAmount' => '6.50',
            'currencyCode' => 'TRY',
            'transactionDate' => 1736294400000,
        ]);

        $this->assertSame('HB-ORDER-12', $order->orderId);
        $this->assertSame('HB-LINE-12', $order->orderItemId);
        $this->assertSame('SKU-HB-12', $order->sku);
        $this->assertSame(2, $order->quantity);
        $this->assertSame(250.75, $order->grossSales);
        $this->assertSame(8.25, $order->discounts);
        $this->assertSame(16.80, $order->commissionFee);
        $this->assertSame('shipped', $order->status);
        $this->assertSame('2025-01-06 00:00:00', $order->orderDate->utc()->toDateTimeString());

        $this->assertSame('HB-LINE-12', $return->orderItemId);
        $this->assertSame(80.00, $return->amount);
        $this->assertSame('2025-01-07 00:00:00', $return->occurredAt->utc()->toDateTimeString());

        $this->assertSame('HB-LINE-12', $fee->orderItemId);
        $this->assertSame('shipping', $fee->type);
        $this->assertSame(6.50, $fee->amount);
        $this->assertSame('2025-01-08 00:00:00', $fee->occurredAt->utc()->toDateTimeString());
    }

    public function test_amazon_mapping_supports_fallback_fields(): void
    {
        $adapter = new AmazonAdapter();

        $order = $adapter->mapOrderItemToCore([
            'amazonOrderId' => 'AMZ-ORDER-1',
            'amazonOrderItemCode' => 'AMZ-LINE-1',
            'purchaseDate' => '2025-01-09T10:00:00+03:00',
            'sellerSku' => 'SKU-AMZ-1',
            'quantity' => 1,
            'currencyCode' => 'USD',
            'itemPrice' => '49.99',
            'promotionDiscount' => '5.00',
            'paymentAmount' => '1.00',
            'orderStatus' => 'delivered',
        ]);

        $return = $adapter->mapReturnToCoreAdjustments([
            'amazonOrderItemCode' => 'AMZ-LINE-1',
            'refund_amount' => '20.00',
            'currency' => 'USD',
            'occurredAt' => '2025-01-10T09:00:00+00:00',
        ]);

        $fee = $adapter->mapFeeToCoreAdjustments([
            'amazonOrderItemCode' => 'AMZ-LINE-1',
            'type' => 'commission',
            'value' => '3.15',
            'currencyCode' => 'USD',
            'postedDate' => 1736553600000,
        ]);

        $this->assertSame('AMZ-ORDER-1', $order->orderId);
        $this->assertSame('AMZ-LINE-1', $order->orderItemId);
        $this->assertSame('USD', $order->currency);
        $this->assertSame(49.99, $order->grossSales);
        $this->assertSame(5.00, $order->discounts);
        $this->assertSame(1.00, $order->paymentFee);
        $this->assertSame('delivered', $order->status);
        $this->assertSame('2025-01-09 07:00:00', $order->orderDate->utc()->toDateTimeString());

        $this->assertSame('AMZ-LINE-1', $return->orderItemId);
        $this->assertSame(20.00, $return->amount);
        $this->assertSame('USD', $return->currency);
        $this->assertSame('2025-01-10 09:00:00', $return->occurredAt->utc()->toDateTimeString());

        $this->assertSame('AMZ-LINE-1', $fee->orderItemId);
        $this->assertSame('commission', $fee->type);
        $this->assertSame(3.15, $fee->amount);
        $this->assertSame('USD', $fee->currency);
        $this->assertSame('2025-01-11 00:00:00', $fee->occurredAt->utc()->toDateTimeString());
    }
}
