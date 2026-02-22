<?php

namespace App\Domains\Marketplaces\Mappers;

class TrendyolOrderMapper
{
    /**
     * @return array<int, array<string,mixed>>
     */
    public function normalize(array $payload): array
    {
        $packages = $payload['items'] ?? $payload['shipmentPackages'] ?? $payload['content'] ?? [];
        if (!is_array($packages)) {
            return [];
        }

        $orders = [];

        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }

            $orderNumber = (string) ($package['orderNumber'] ?? $package['orderId'] ?? '');
            if ($orderNumber === '') {
                continue;
            }

            $shipmentPackageId = (string) ($package['shipmentPackageId'] ?? $package['id'] ?? $package['packageId'] ?? '');
            $lines = $package['lines'] ?? $package['lineItems'] ?? $package['items'] ?? [];
            if (!is_array($lines)) {
                $lines = [];
            }

            $currency = (string) (
                $package['currencyCode']
                ?? $package['currency']
                ?? data_get($package, 'invoice.totalPrice.currencyCode', 'TRY')
            );

            $totals = [
                'totalPrice' => (float) ($package['totalPrice'] ?? data_get($package, 'invoice.totalPrice', 0)),
                'totalDiscount' => (float) ($package['totalDiscount'] ?? 0),
                'currency' => $currency,
                'cargoProvider' => $package['cargoProviderName'] ?? $package['cargoProvider'] ?? null,
                'trackingNumber' => $package['trackingNumber'] ?? data_get($package, 'cargoTrackingNumber'),
            ];

            $normalizedItems = [];
            foreach ($lines as $line) {
                if (!is_array($line)) {
                    continue;
                }

                $barcode = (string) ($line['barcode'] ?? $line['productBarcode'] ?? '');
                $sku = (string) ($line['merchantSku'] ?? $line['sku'] ?? $barcode ?: 'UNKNOWN');
                $quantity = (int) ($line['quantity'] ?? $line['qty'] ?? 1);

                $normalizedItems[] = [
                    'sku' => $sku,
                    'barcode' => $barcode !== '' ? $barcode : null,
                    'shipmentPackageId' => $shipmentPackageId !== '' ? $shipmentPackageId : null,
                    'qty' => $quantity,
                    'sale_price' => (float) ($line['price'] ?? $line['salePrice'] ?? $line['amount'] ?? 0),
                    'sale_vat' => (float) ($line['vatBaseAmount'] ?? $line['vatAmount'] ?? 0),
                    'shipping_amount' => (float) ($line['cargoPrice'] ?? 0),
                    'shipping_vat' => (float) ($line['cargoVat'] ?? 0),
                    'commission_amount' => (float) ($line['commission'] ?? $line['commissionAmount'] ?? 0),
                    'commission_vat' => (float) ($line['commissionVat'] ?? 0),
                    'service_fee_amount' => (float) ($line['serviceFee'] ?? 0),
                    'service_fee_vat' => (float) ($line['serviceFeeVat'] ?? 0),
                    'variant_id' => isset($line['productSize']) ? (string) $line['productSize'] : null,
                    'raw_payload' => [
                        'line' => $line,
                        'cargoProvider' => $totals['cargoProvider'],
                        'trackingNumber' => $totals['trackingNumber'],
                        'shipmentPackageId' => $shipmentPackageId,
                    ],
                ];
            }

            $orders[] = [
                'marketplace_order_id' => $orderNumber,
                'order_number' => $orderNumber,
                'order_date' => $package['orderDate'] ?? $package['createdDate'] ?? $package['lastModifiedDate'] ?? null,
                'status' => $package['packageStatus'] ?? $package['status'] ?? 'Created',
                'currency' => $currency,
                'totals' => $totals,
                'items' => $normalizedItems,
                'raw_payload' => [
                    'package' => $package,
                    'invoiceAddress' => $package['invoiceAddress'] ?? null,
                    'shippingAddress' => $package['shippingAddress'] ?? null,
                    'invoice' => $package['invoice'] ?? null,
                ],
            ];
        }

        return $orders;
    }
}
