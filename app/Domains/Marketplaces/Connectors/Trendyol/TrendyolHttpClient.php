<?php

namespace App\Domains\Marketplaces\Connectors\Trendyol;

use App\Domains\Marketplaces\Services\MarketplaceHttpClient;
use App\Domains\Marketplaces\Services\SensitiveValueMasker;
use App\Domains\Marketplaces\Services\SyncLogService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use RuntimeException;

class TrendyolHttpClient
{
    public function __construct(
        private readonly MarketplaceHttpClient $httpClient,
        private readonly SyncLogService $syncLogService,
        private readonly SensitiveValueMasker $masker,
        private readonly ?int $syncJobId = null,
    ) {
    }

    public function get(
        string $baseUrl,
        string $path,
        array $credentials,
        array $headers = [],
        array $query = [],
        int $timeoutSeconds = 20
    ): Response {
        $apiKey = (string) ($credentials['api_key'] ?? '');
        $apiSecret = (string) ($credentials['api_secret'] ?? '');
        $requestHeaders = array_merge([
            'storeFrontCode' => (string) ($credentials['store_front_code'] ?? ''),
            'Accept' => 'application/json',
        ], $headers);
        if (($requestHeaders['storeFrontCode'] ?? '') === '') {
            throw new RuntimeException('Trendyol storeFrontCode header zorunludur.');
        }

        $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        $startedAt = microtime(true);
        $this->log('trendyol.request', [
            'url' => $url,
            'query' => $this->masker->maskArray($query),
            'headers' => $this->masker->maskArray($requestHeaders),
        ]);

        $response = null;
        $backoffMs = [250, 750, 1500];
        $attempt = 0;
        while (true) {
            try {
                $response = $this->httpClient
                    ->build($baseUrl, $timeoutSeconds)
                    ->withBasicAuth($apiKey, $apiSecret)
                    ->withHeaders($requestHeaders)
                    ->get($path, $query);
            } catch (ConnectionException $e) {
                if ($attempt >= count($backoffMs)) {
                    throw $e;
                }
                usleep($backoffMs[$attempt] * 1000);
                $attempt++;
                continue;
            }

            $status = $response->status();
            $retryable = $status === 429 || ($status >= 500 && $status <= 599);
            if (!$retryable || $attempt >= count($backoffMs)) {
                break;
            }

            usleep($backoffMs[$attempt] * 1000);
            $attempt++;
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $json = (array) $response->json();
        $this->log('trendyol.response', [
            'url' => $url,
            'status' => $response->status(),
            'duration_ms' => $durationMs,
            'page' => $query['page'] ?? null,
            'size' => $query['size'] ?? null,
            'totalPages' => $json['totalPages'] ?? null,
            'totalElements' => $json['totalElements'] ?? null,
        ]);

        return $response;
    }

    private function log(string $message, array $context): void
    {
        if (!$this->syncJobId) {
            return;
        }

        $this->syncLogService->info($this->syncJobId, $message, $context);
    }
}
