<?php

namespace App\Domains\Marketplaces\Connectors\N11;

use App\Domains\Marketplaces\Connectors\BaseRealConnector;
use App\Domains\Marketplaces\Contracts\MarketplaceConnectorInterface;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Arr;

class N11Connector extends BaseRealConnector implements MarketplaceConnectorInterface
{
    public function fetchOrders(
        DateTimeInterface $from,
        DateTimeInterface $to,
        array $filters = [],
        ?int $page = 0,
        ?int $size = 200
    ): array
    {
        $response = $this->requestPaged(
            (string) config('marketplaces.n11.endpoints.orders'),
            [
                'startDate' => Carbon::instance($from)->toIso8601String(),
                'endDate' => Carbon::instance($to)->toIso8601String(),
                'status' => $filters['status'] ?? null,
            ],
            min(max((int) ($size ?? config('marketplaces.n11.page_size', 200)), 1), 200)
        );

        return ['items' => $this->normalizeOrders($response['items']), 'next_page_token' => null];
    }

    public function fetchReturns(string $from, string $to, ?string $pageToken = null): array
    {
        $response = $this->requestPaged(
            (string) config('marketplaces.n11.endpoints.returns'),
            [
                'startDate' => Carbon::parse($from)->toIso8601String(),
                'endDate' => Carbon::parse($to)->toIso8601String(),
            ],
            (int) config('marketplaces.n11.page_size', 200)
        );

        return ['items' => $this->normalizeReturns($response['items']), 'next_page_token' => null];
    }

    public function fetchPayouts(string $from, string $to, ?string $pageToken = null): array
    {
        $response = $this->requestPaged(
            (string) config('marketplaces.n11.endpoints.payouts'),
            [
                'startDate' => Carbon::parse($from)->toDateString(),
                'endDate' => Carbon::parse($to)->toDateString(),
            ],
            (int) config('marketplaces.n11.page_size', 200)
        );

        return ['items' => $this->normalizePayouts($response['items']), 'next_page_token' => null];
    }

    public function fetchPayoutTransactions(string $payoutReference, ?string $pageToken = null): array
    {
        $path = str_replace(
            '{payout_reference}',
            $payoutReference,
            (string) config('marketplaces.n11.endpoints.payout_transactions')
        );
        $response = $this->requestPaged($path, [], (int) config('marketplaces.n11.page_size', 200));

        return ['items' => $this->normalizePayoutTransactions($response['items']), 'next_page_token' => null];
    }

    private function requestPaged(string $path, array $baseQuery, int $size): array
    {
        $credentials = $this->credentials();
        $headers = (new N11AuthHeaderBuilder())->headers($credentials);
        $baseUrl = (string) config('marketplaces.n11.base_url');
        $timeout = (int) config('marketplaces.n11.timeout', 20);

        $items = [];
        $page = 1;
        do {
            $query = array_filter(array_merge($baseQuery, ['page' => $page, 'size' => $size]), fn ($v) => $v !== null && $v !== '');
            $response = $this->httpClient
                ->build($baseUrl, $timeout)
                ->withHeaders($headers)
                ->get($path, $query);

            $json = (array) $response->json();
            $chunk = Arr::get($json, 'items', Arr::get($json, 'content', Arr::get($json, 'data', [])));
            $chunk = is_array($chunk) ? $chunk : [];
            $items = array_merge($items, $chunk);

            $totalPages = Arr::get($json, 'totalPages');
            if (is_numeric($totalPages)) {
                $page++;
                $hasMore = $page <= (int) $totalPages;
            } else {
                $page++;
                $hasMore = count($chunk) >= $size;
            }
        } while ($hasMore);

        return ['items' => $items];
    }

    private function normalizeOrders(array $rows): array
    {
        return collect($rows)->map(function ($row) {
            if (!is_array($row)) {
                return null;
            }

            $id = (string) ($row['orderNumber'] ?? $row['orderId'] ?? $row['id'] ?? '');
            if ($id === '') {
                return null;
            }

            return [
                'marketplace_order_id' => $id,
                'order_number' => $id,
                'status' => (string) ($row['status'] ?? 'NEW'),
                'currency' => (string) ($row['currency'] ?? $row['currencyCode'] ?? 'TRY'),
                'order_date' => $row['createdDate'] ?? $row['orderDate'] ?? now()->toDateTimeString(),
                'totals' => ['gross' => (float) ($row['totalPrice'] ?? $row['amount'] ?? 0)],
                'items' => is_array($row['items'] ?? null) ? $row['items'] : [],
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

            $orderId = (string) ($row['orderNumber'] ?? $row['orderId'] ?? '');
            $returnId = (string) ($row['returnId'] ?? $row['id'] ?? '');
            if ($orderId === '' || $returnId === '') {
                return null;
            }

            return [
                'marketplace_order_id' => $orderId,
                'marketplace_return_id' => $returnId,
                'status' => (string) ($row['status'] ?? 'OPEN'),
                'amounts' => [
                    'refund_total' => (float) ($row['amount'] ?? 0),
                    'currency' => (string) ($row['currency'] ?? $row['currencyCode'] ?? 'TRY'),
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

            $reference = (string) ($row['payoutId'] ?? $row['id'] ?? '');
            if ($reference === '') {
                return null;
            }

            return [
                'payout_reference' => $reference,
                'period_start' => (string) ($row['periodStart'] ?? now()->startOfMonth()->toDateString()),
                'period_end' => (string) ($row['periodEnd'] ?? now()->toDateString()),
                'paid_amount' => (float) ($row['paidAmount'] ?? $row['amount'] ?? 0),
                'paid_date' => $row['paidDate'] ?? null,
                'currency' => (string) ($row['currency'] ?? $row['currencyCode'] ?? 'TRY'),
                'status' => (string) ($row['status'] ?? 'EXPECTED'),
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
                'type' => (string) ($row['type'] ?? $row['transactionType'] ?? 'ADJUSTMENT'),
                'amount' => (float) ($row['amount'] ?? 0),
                'vat_amount' => (float) ($row['vatAmount'] ?? $row['vat_amount'] ?? 0),
                'meta' => ['reference' => $row['reference'] ?? $row['id'] ?? null],
                'raw_payload' => $row,
            ];
        })->filter()->values()->all();
    }
}
