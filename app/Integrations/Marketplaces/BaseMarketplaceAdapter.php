<?php

namespace App\Integrations\Marketplaces;

use App\Models\MarketplaceAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseMarketplaceAdapter implements MarketplaceAdapterInterface
{
    protected function httpClient(MarketplaceAccount $account): PendingRequest
    {
        return Http::retry(3, 500)
            ->timeout(30)
            ->acceptJson()
            ->withHeaders($this->defaultHeaders($account));
    }

    /**
     * @return array<string, string>
     */
    abstract protected function defaultHeaders(MarketplaceAccount $account): array;

    protected function sendRequest(
        MarketplaceAccount $account,
        string $method,
        string $url,
        array $options = []
    ): Response {
        $response = $this->httpClient($account)->send($method, $url, $options);

        if ($response->status() === 429) {
            $this->handleRateLimit($response);
            $response = $this->httpClient($account)->send($method, $url, $options);
        }

        return $response;
    }

    protected function handleRateLimit(Response $response): void
    {
        $retryAfter = (int) ($response->header('Retry-After') ?? 0);
        if ($retryAfter <= 0) {
            $retryAfter = 2;
        }

        sleep(min($retryAfter, 10));
    }
}
