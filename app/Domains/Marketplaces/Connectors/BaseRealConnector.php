<?php

namespace App\Domains\Marketplaces\Connectors;

use App\Domains\Marketplaces\Services\MarketplaceHttpClient;
use App\Domains\Marketplaces\Services\SensitiveValueMasker;
use App\Domains\Marketplaces\Services\SyncLogService;
use App\Models\MarketplaceAccount;
use Illuminate\Http\Client\Response;

abstract class BaseRealConnector
{
    public function __construct(
        protected MarketplaceAccount $account,
        protected MarketplaceHttpClient $httpClient,
        protected SyncLogService $syncLogService,
        protected SensitiveValueMasker $masker,
        protected ?int $syncJobId = null,
    ) {
    }

    protected function credentials(): array
    {
        return (array) ($this->account->credentials ?? $this->account->credentials_json ?? []);
    }

    protected function logRequest(string $method, string $url, array $headers, array $query = []): void
    {
        if (!$this->syncJobId) {
            return;
        }

        $this->syncLogService->info($this->syncJobId, 'connector.request', [
            'method' => $method,
            'url' => $url,
            'headers' => $this->masker->maskArray($headers),
            'query' => $query,
        ]);
    }

    protected function logResponse(Response $response): void
    {
        if (!$this->syncJobId) {
            return;
        }

        $body = $response->json();
        if (!is_array($body)) {
            $body = ['body' => substr((string) $response->body(), 0, 500)];
        }

        $this->syncLogService->info($this->syncJobId, 'connector.response', [
            'status' => $response->status(),
            'headers' => $this->masker->maskArray($response->headers()),
            'body' => $this->masker->maskArray($body),
        ]);
    }
}

