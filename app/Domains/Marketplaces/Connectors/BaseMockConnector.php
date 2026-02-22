<?php

namespace App\Domains\Marketplaces\Connectors;

use App\Domains\Marketplaces\Contracts\MarketplaceConnectorInterface;
use App\Models\MarketplaceAccount;
use Illuminate\Support\Carbon;
use DateTimeInterface;

abstract class BaseMockConnector implements MarketplaceConnectorInterface
{
    public function __construct(protected MarketplaceAccount $account)
    {
    }

    abstract protected function marketplaceCode(): string;

    public function fetchOrders(
        DateTimeInterface $from,
        DateTimeInterface $to,
        array $filters = [],
        ?int $page = 0,
        ?int $size = 200
    ): array
    {
        $date = Carbon::instance($from)->toDateString();
        return [
            'items' => [[
                'marketplace_order_id' => strtoupper($this->marketplaceCode()) . '-ORD-' . $date . '-001',
                'order_date' => Carbon::instance($from)->toISOString(),
                'status' => 'DELIVERED',
                'currency' => 'TRY',
                'totals' => ['gross' => 600.0, 'net' => 480.0],
                'items' => [[
                    'sku' => 'SKU-001',
                    'variant_id' => 'VAR-001',
                    'qty' => 1,
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
                ]],
            ]],
            'next_page_token' => null,
        ];
    }

    public function fetchReturns(string $from, string $to, ?string $pageToken = null): array
    {
        return [
            'items' => [],
            'next_page_token' => null,
        ];
    }

    public function fetchPayouts(string $from, string $to, ?string $pageToken = null): array
    {
        return [
            'items' => [[
                'payout_reference' => strtoupper($this->marketplaceCode()) . '-PO-001',
                'period_start' => Carbon::parse($from)->toDateString(),
                'period_end' => Carbon::parse($to)->toDateString(),
                'paid_amount' => 195.83,
                'paid_date' => Carbon::parse($to)->addDays(2)->toDateString(),
                'currency' => 'TRY',
                'status' => 'PAID',
            ]],
            'next_page_token' => null,
        ];
    }

    public function fetchPayoutTransactions(string $payoutReference, ?string $pageToken = null): array
    {
        return [
            'items' => [[
                'type' => 'ORDER',
                'amount' => 195.83,
                'vat_amount' => 0,
                'meta' => ['payout_reference' => $payoutReference],
            ]],
            'next_page_token' => null,
        ];
    }
}
