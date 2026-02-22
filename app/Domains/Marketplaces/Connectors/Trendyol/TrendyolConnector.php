<?php

namespace App\Domains\Marketplaces\Connectors\Trendyol;

use App\Domains\Marketplaces\Connectors\BaseRealConnector;
use App\Domains\Marketplaces\Contracts\MarketplaceConnectorInterface;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class TrendyolConnector extends BaseRealConnector implements MarketplaceConnectorInterface
{
    public function fetchFinanceSettlements(
        string $from,
        string $to,
        string $type = 'Sale',
        int $page = 0,
        int $size = 500
    ): array {
        $service = new TrendyolFinanceService(
            new TrendyolHttpClient($this->httpClient, $this->syncLogService, $this->masker, $this->syncJobId),
            $this->credentials()
        );

        return $service->fetchSettlements($from, $to, $type, $size);
    }

    public function fetchFinanceOtherFinancials(
        string $from,
        string $to,
        string $type = 'PaymentOrder',
        int $page = 0,
        int $size = 500
    ): array {
        $service = new TrendyolFinanceService(
            new TrendyolHttpClient($this->httpClient, $this->syncLogService, $this->masker, $this->syncJobId),
            $this->credentials()
        );

        return $service->fetchOtherFinancials($from, $to, $type, $size);
    }

    public function fetchOrders(
        DateTimeInterface $from,
        DateTimeInterface $to,
        array $filters = [],
        ?int $page = 0,
        ?int $size = 200
    ): array {
        $credentials = $this->credentials();
        $headers = (new TrendyolAuthHeaderBuilder())->headers($credentials);
        $sellerId = (string) ($credentials['seller_id'] ?? '');
        $baseUrl = (string) config('marketplaces.trendyol.base_url');
        $http = new TrendyolHttpClient($this->httpClient, $this->syncLogService, $this->masker, $this->syncJobId);

        $size = min(max((int) ($size ?? 200), 1), 200);
        $shipmentPackageIds = (array) ($filters['shipmentPackageIds'] ?? []);
        if (count($shipmentPackageIds) > 50) {
            throw new InvalidArgumentException('shipmentPackageIds max 50 olmalidir.');
        }

        $orderByDirection = strtoupper((string) ($filters['orderByDirection'] ?? 'ASC'));
        if (!in_array($orderByDirection, ['ASC', 'DESC'], true)) {
            $orderByDirection = 'ASC';
        }
        $orderByField = (string) ($filters['orderByField'] ?? 'PackageLastModifiedDate');

        $chunks = $this->splitTo14DayChunks($from, $to);
        $items = [];
        $lastMeta = [];

        foreach ($chunks as [$chunkFrom, $chunkTo]) {
            $currentPage = max((int) ($page ?? 0), 0);
            do {
                $query = array_filter([
                    'startDate' => $chunkFrom->getTimestampMs(),
                    'endDate' => $chunkTo->getTimestampMs(),
                    'page' => $currentPage,
                    'size' => $size,
                    'orderNumber' => $filters['orderNumber'] ?? null,
                    'status' => $filters['status'] ?? null,
                    'orderByField' => $orderByField,
                    'orderByDirection' => $orderByDirection,
                    'shipmentPackageIds' => $shipmentPackageIds !== [] ? implode(',', $shipmentPackageIds) : null,
                ], fn ($value) => $value !== null && $value !== '');

                $response = $http->get(
                    $baseUrl,
                    "/integration/order/sellers/{$sellerId}/orders",
                    $credentials,
                    $headers,
                    $query,
                    (int) config('marketplaces.trendyol.timeout', 20)
                );

                $json = (array) $response->json();
                $chunkItems = Arr::get($json, 'shipmentPackages', Arr::get($json, 'content', []));
                $chunkItems = is_array($chunkItems) ? $chunkItems : [];
                $items = array_merge($items, $chunkItems);

                $totalPages = Arr::get($json, 'totalPages');
                $totalElements = Arr::get($json, 'totalElements');
                $lastMeta = [
                    'totalPages' => $totalPages,
                    'totalElements' => $totalElements,
                    'chunkStart' => $chunkFrom->toISOString(),
                    'chunkEnd' => $chunkTo->toISOString(),
                ];

                if (is_numeric($totalPages)) {
                    $currentPage++;
                    $hasMore = $currentPage < (int) $totalPages;
                } else {
                    $currentPage++;
                    $hasMore = count($chunkItems) >= $size;
                }
            } while ($hasMore);
        }

        return ['items' => $items, 'next_page_token' => null, 'meta' => $lastMeta];
    }

    public function fetchReturns(string $from, string $to, ?string $pageToken = null): array
    {
        // TODO: implement Trendyol returns endpoint mapping.
        return ['items' => [], 'next_page_token' => null];
    }

    public function fetchPayouts(string $from, string $to, ?string $pageToken = null): array
    {
        $sale = $this->fetchFinanceSettlements($from, $to, 'Sale');
        $returns = $this->fetchFinanceSettlements($from, $to, 'Return');
        $paymentOrders = $this->fetchFinanceOtherFinancials($from, $to, 'PaymentOrder');

        return [
            'items' => array_merge($sale, $returns),
            'payment_orders' => $paymentOrders,
            'next_page_token' => null,
            'meta' => ['source' => 'trendyol_finance'],
        ];
    }

    public function fetchPayoutTransactions(string $payoutReference, ?string $pageToken = null): array
    {
        // Settlements bazli transactionlar fetchPayouts() tarafinda toplu olarak aliniyor.
        return ['items' => [], 'next_page_token' => null];
    }

    /**
     * @return array<int, array{0:Carbon,1:Carbon}>
     */
    private function splitTo14DayChunks(DateTimeInterface $from, DateTimeInterface $to): array
    {
        $start = Carbon::instance($from)->startOfMillisecond();
        $end = Carbon::instance($to)->startOfMillisecond();
        if ($start->gt($end)) {
            return [];
        }

        $chunks = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $chunkEnd = $cursor->copy()->addDays(14)->subMillisecond();
            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end->copy();
            }

            $chunks[] = [$cursor->copy(), $chunkEnd->copy()];
            $cursor = $chunkEnd->copy()->addMillisecond();
        }

        return $chunks;
    }
}
