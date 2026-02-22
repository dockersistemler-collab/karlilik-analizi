<?php

namespace App\Domains\Marketplaces\Connectors\Trendyol;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class TrendyolFinanceService
{
    public function __construct(
        private readonly TrendyolHttpClient $httpClient,
        private readonly array $credentials
    ) {
    }

    public function fetchSettlements(string $from, string $to, string $transactionType, int $size = 500): array
    {
        $sellerId = (string) ($this->credentials['seller_id'] ?? '');
        $baseUrl = (string) config('marketplaces.trendyol.base_url', 'https://apigw.trendyol.com');
        $path = "/integration/finance/che/sellers/{$sellerId}/settlements";

        $all = [];
        foreach ($this->chunkRange($from, $to, 15) as [$startMs, $endMs]) {
            $page = 0;
            do {
                $response = $this->httpClient->get($baseUrl, $path, $this->credentials, [], [
                    'startDate' => $startMs,
                    'endDate' => $endMs,
                    'transactionType' => $transactionType,
                    'page' => $page,
                    'size' => $size,
                ], (int) config('marketplaces.trendyol.timeout', 20));

                $json = (array) $response->json();
                $content = (array) ($json['content'] ?? []);
                $all = array_merge($all, $content);

                $totalPages = (int) Arr::get($json, 'totalPages', 0);
                $page++;
            } while ($totalPages > 0 && $page < $totalPages);
        }

        return $all;
    }

    public function fetchOtherFinancials(string $from, string $to, string $transactionType, int $size = 500): array
    {
        $sellerId = (string) ($this->credentials['seller_id'] ?? '');
        $baseUrl = (string) config('marketplaces.trendyol.base_url', 'https://apigw.trendyol.com');
        $path = "/integration/finance/che/sellers/{$sellerId}/otherfinancials";

        $all = [];
        foreach ($this->chunkRange($from, $to, 15) as [$startMs, $endMs]) {
            $page = 0;
            do {
                $response = $this->httpClient->get($baseUrl, $path, $this->credentials, [], [
                    'startDate' => $startMs,
                    'endDate' => $endMs,
                    'transactionType' => $transactionType,
                    'page' => $page,
                    'size' => $size,
                ], (int) config('marketplaces.trendyol.timeout', 20));

                $json = (array) $response->json();
                $content = (array) ($json['content'] ?? []);
                $all = array_merge($all, $content);

                $totalPages = (int) Arr::get($json, 'totalPages', 0);
                $page++;
            } while ($totalPages > 0 && $page < $totalPages);
        }

        return $all;
    }

    /**
     * Splits date range into max-day windows as required by Trendyol Finance endpoints.
     *
     * @return array<int, array{0:int,1:int}>
     */
    private function chunkRange(string $from, string $to, int $maxDays): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();
        if ($start->gt($end)) {
            return [];
        }

        $windows = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $windowEnd = $cursor->copy()->addDays($maxDays - 1)->endOfDay();
            if ($windowEnd->gt($end)) {
                $windowEnd = $end->copy();
            }

            $windows[] = [
                (int) $cursor->getTimestampMs(),
                (int) $windowEnd->getTimestampMs(),
            ];

            $cursor = $windowEnd->copy()->addMillisecond();
        }

        return $windows;
    }
}

