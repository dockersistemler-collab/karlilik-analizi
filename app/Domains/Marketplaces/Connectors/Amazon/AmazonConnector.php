<?php

namespace App\Domains\Marketplaces\Connectors\Amazon;

use App\Domains\Marketplaces\Connectors\BaseRealConnector;
use App\Domains\Marketplaces\Contracts\MarketplaceConnectorInterface;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Arr;

class AmazonConnector extends BaseRealConnector implements MarketplaceConnectorInterface
{
    public function fetchOrders(
        DateTimeInterface $from,
        DateTimeInterface $to,
        array $filters = [],
        ?int $page = 0,
        ?int $size = 200
    ): array
    {
        $credentials = $this->credentials();
        $headers = (new AmazonAuthHeaderBuilder())->headers($credentials);
        $baseUrl = (string) config('marketplaces.amazon.base_url');
        $timeout = (int) config('marketplaces.amazon.timeout', 20);
        $path = (string) config('marketplaces.amazon.endpoints.orders');
        $marketplaceId = (string) ($credentials['marketplace_id'] ?? config('marketplaces.amazon.marketplace_id'));
        $maxResults = min(max((int) ($size ?? config('marketplaces.amazon.page_size', 100)), 1), 100);

        $nextToken = null;
        $items = [];
        do {
            $query = $nextToken
                ? ['NextToken' => $nextToken]
                : [
                    'MarketplaceIds' => $marketplaceId,
                    'CreatedAfter' => Carbon::instance($from)->toIso8601String(),
                    'CreatedBefore' => Carbon::instance($to)->toIso8601String(),
                    'MaxResultsPerPage' => $maxResults,
                ];

            $response = $this->httpClient
                ->build($baseUrl, $timeout)
                ->withHeaders($headers)
                ->get($path, $query);

            $json = (array) $response->json();
            $payload = Arr::get($json, 'payload', []);
            $orders = Arr::get($payload, 'Orders', Arr::get($json, 'orders', []));
            $orders = is_array($orders) ? $orders : [];
            $items = array_merge($items, $orders);
            $nextToken = Arr::get($payload, 'NextToken', Arr::get($json, 'nextToken'));
        } while (!empty($nextToken));

        return ['items' => $this->normalizeOrders($items), 'next_page_token' => null];
    }

    public function fetchReturns(string $from, string $to, ?string $pageToken = null): array
    {
        $credentials = $this->credentials();
        $headers = (new AmazonAuthHeaderBuilder())->headers($credentials);
        $baseUrl = (string) config('marketplaces.amazon.base_url');
        $timeout = (int) config('marketplaces.amazon.timeout', 20);
        $path = (string) config('marketplaces.amazon.endpoints.returns');
        $marketplaceId = (string) ($credentials['marketplace_id'] ?? config('marketplaces.amazon.marketplace_id'));

        $response = $this->httpClient
            ->build($baseUrl, $timeout)
            ->withHeaders($headers)
            ->get($path, [
                'MarketplaceIds' => $marketplaceId,
                'PostedAfter' => Carbon::parse($from)->toIso8601String(),
                'PostedBefore' => Carbon::parse($to)->toIso8601String(),
            ]);

        $json = (array) $response->json();
        $rows = Arr::get($json, 'payload.returns', Arr::get($json, 'returns', Arr::get($json, 'items', [])));
        $rows = is_array($rows) ? $rows : [];

        return ['items' => $this->normalizeReturns($rows), 'next_page_token' => null];
    }

    public function fetchPayouts(string $from, string $to, ?string $pageToken = null): array
    {
        $credentials = $this->credentials();
        $headers = (new AmazonAuthHeaderBuilder())->headers($credentials);
        $baseUrl = (string) config('marketplaces.amazon.base_url');
        $timeout = (int) config('marketplaces.amazon.timeout', 20);
        $path = (string) config('marketplaces.amazon.endpoints.payouts');
        $maxResults = min(max((int) config('marketplaces.amazon.page_size', 100), 1), 100);
        $nextToken = $pageToken;
        $rows = [];

        do {
            $query = $nextToken
                ? ['NextToken' => $nextToken]
                : [
                    'FinancialEventGroupStartedAfter' => Carbon::parse($from)->toIso8601String(),
                    'FinancialEventGroupStartedBefore' => Carbon::parse($to)->toIso8601String(),
                    'MaxResultsPerPage' => $maxResults,
                ];

            $response = $this->httpClient
                ->build($baseUrl, $timeout)
                ->withHeaders($headers)
                ->get($path, $query);

            $json = (array) $response->json();
            $payload = Arr::get($json, 'payload', []);
            $chunk = Arr::get($payload, 'FinancialEventGroupList', Arr::get($json, 'items', []));
            $chunk = is_array($chunk) ? $chunk : [];
            $rows = array_merge($rows, $chunk);
            $nextToken = Arr::get($payload, 'NextToken', Arr::get($json, 'nextToken'));
        } while (!empty($nextToken));

        return ['items' => $this->normalizePayouts($rows), 'next_page_token' => null];
    }

    public function fetchPayoutTransactions(string $payoutReference, ?string $pageToken = null): array
    {
        $credentials = $this->credentials();
        $headers = (new AmazonAuthHeaderBuilder())->headers($credentials);
        $baseUrl = (string) config('marketplaces.amazon.base_url');
        $timeout = (int) config('marketplaces.amazon.timeout', 20);
        $path = str_replace(
            '{payout_reference}',
            $payoutReference,
            (string) config('marketplaces.amazon.endpoints.payout_transactions')
        );

        $response = $this->httpClient
            ->build($baseUrl, $timeout)
            ->withHeaders($headers)
            ->get($path, []);

        $json = (array) $response->json();
        $payload = Arr::get($json, 'payload', []);
        $rows = Arr::get($payload, 'FinancialEvents', Arr::get($json, 'items', []));
        $rows = is_array($rows) ? $rows : [];

        return ['items' => $this->normalizePayoutTransactions($rows), 'next_page_token' => null];
    }

    private function normalizeOrders(array $rows): array
    {
        return collect($rows)->map(function ($row) {
            if (!is_array($row)) {
                return null;
            }

            $id = (string) ($row['AmazonOrderId'] ?? $row['orderId'] ?? '');
            if ($id === '') {
                return null;
            }

            return [
                'marketplace_order_id' => $id,
                'order_number' => $id,
                'status' => (string) ($row['OrderStatus'] ?? $row['status'] ?? 'NEW'),
                'currency' => (string) ($row['OrderTotal']['CurrencyCode'] ?? $row['currency'] ?? 'TRY'),
                'order_date' => $row['PurchaseDate'] ?? $row['purchaseDate'] ?? now()->toDateTimeString(),
                'totals' => [
                    'gross' => (float) ($row['OrderTotal']['Amount'] ?? $row['amount'] ?? 0),
                ],
                'items' => [],
                'raw_payload' => $row,
            ];
        })->filter()->values()->all();
    }

    private function normalizeReturns(array $rows): array
    {
        return collect($rows)->map(function ($row) {
            if (!is_array($row)) {
                return null;
            }

            $orderId = (string) ($row['AmazonOrderId'] ?? $row['orderId'] ?? '');
            $returnId = (string) ($row['ReturnItemId'] ?? $row['returnId'] ?? $row['id'] ?? '');
            if ($orderId === '' || $returnId === '') {
                return null;
            }

            return [
                'marketplace_order_id' => $orderId,
                'marketplace_return_id' => $returnId,
                'status' => (string) ($row['ReturnStatus'] ?? $row['status'] ?? 'OPEN'),
                'amounts' => [
                    'refund_total' => (float) ($row['RefundAmount']['Amount'] ?? $row['amount'] ?? 0),
                    'currency' => (string) ($row['RefundAmount']['CurrencyCode'] ?? $row['currency'] ?? 'TRY'),
                ],
                'raw_payload' => $row,
            ];
        })->filter()->values()->all();
    }

    private function normalizePayouts(array $rows): array
    {
        return collect($rows)->map(function ($row) {
            if (!is_array($row)) {
                return null;
            }

            $reference = (string) ($row['FinancialEventGroupId'] ?? $row['id'] ?? '');
            if ($reference === '') {
                return null;
            }

            return [
                'payout_reference' => $reference,
                'period_start' => (string) ($row['FundTransferStatusDate'] ?? now()->startOfMonth()->toDateString()),
                'period_end' => (string) ($row['FundTransferStatusDate'] ?? now()->toDateString()),
                'paid_amount' => (float) ($row['OriginalTotal']['Amount'] ?? $row['amount'] ?? 0),
                'paid_date' => $row['FundTransferStatusDate'] ?? null,
                'currency' => (string) ($row['OriginalTotal']['CurrencyCode'] ?? 'TRY'),
                'status' => (string) ($row['ProcessingStatus'] ?? 'EXPECTED'),
                'raw_payload' => $row,
            ];
        })->filter()->values()->all();
    }

    private function normalizePayoutTransactions(array $rows): array
    {
        return collect($rows)->map(function ($row) {
            if (!is_array($row)) {
                return null;
            }

            return [
                'type' => (string) ($row['type'] ?? $row['TransactionType'] ?? 'ADJUSTMENT'),
                'amount' => (float) ($row['amount'] ?? $row['ChargeAmount']['Amount'] ?? 0),
                'vat_amount' => (float) ($row['vat_amount'] ?? 0),
                'meta' => ['reference' => $row['id'] ?? $row['TransactionId'] ?? null],
                'raw_payload' => $row,
            ];
        })->filter()->values()->all();
    }
}
