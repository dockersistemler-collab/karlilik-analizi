<?php

namespace App\Services\Marketplaces\Clients;

use App\Models\MarketplaceStore;
use App\Services\Marketplaces\Contracts\MarketplaceClientInterface;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractStubClient implements MarketplaceClientInterface
{
    abstract protected function marketplaceCode(): string;

    public function fetchThreads(MarketplaceStore $store, string $channel, ?CarbonInterface $since = null): array
    {
        $endpoint = $this->resolveEndpoint($store, 'threads_endpoint', '/communication/threads');
        $client = $this->client($store);
        if (!$client || $endpoint === '') {
            return [];
        }

        $response = $client->get($endpoint, array_filter([
            'channel' => $channel,
            'since' => $since?->toIso8601String(),
            'store_id' => $store->store_external_id,
        ], static fn ($v) => $v !== null && $v !== ''));

        if (!$response->successful()) {
            $this->logFailedCall('fetchThreads', $store, $response->status(), $response->body());
            return [];
        }

        $rows = $this->extractRows($response->json());
        return array_values(array_filter(array_map(
            fn ($row) => $this->mapThreadRow(is_array($row) ? $row : []),
            $rows
        ), fn ($row) => is_array($row) && ($row['external_thread_id'] ?? '') !== ''));
    }

    public function fetchThreadMessages(MarketplaceStore $store, string $externalThreadId): array
    {
        $endpoint = $this->resolveEndpoint($store, 'messages_endpoint', '/communication/threads/{external_thread_id}/messages', [
            'external_thread_id' => $externalThreadId,
        ]);
        $client = $this->client($store);
        if (!$client || $endpoint === '') {
            return [];
        }

        $response = $client->get($endpoint, array_filter([
            'store_id' => $store->store_external_id,
        ], static fn ($v) => $v !== null && $v !== ''));

        if (!$response->successful()) {
            $this->logFailedCall('fetchThreadMessages', $store, $response->status(), $response->body());
            return [];
        }

        $rows = $this->extractRows($response->json());
        return array_values(array_filter(array_map(
            fn ($row) => $this->mapMessageRow(is_array($row) ? $row : []),
            $rows
        ), fn ($row) => is_array($row) && trim((string) ($row['body'] ?? '')) !== ''));
    }

    public function sendReply(MarketplaceStore $store, string $externalThreadId, string $message): array
    {
        $endpoint = $this->resolveEndpoint($store, 'send_reply_endpoint', '/communication/threads/{external_thread_id}/reply', [
            'external_thread_id' => $externalThreadId,
        ]);
        $client = $this->client($store);
        if (!$client || $endpoint === '') {
            return ['ok' => false, 'error' => 'missing_endpoint_or_credentials'];
        }

        $method = strtoupper((string) Arr::get($this->credentials($store), 'send_reply_method', 'POST'));
        $payload = [
            'message' => $message,
            'body' => $message,
            'store_id' => $store->store_external_id,
            'thread_id' => $externalThreadId,
        ];

        try {
            $response = match ($method) {
                'PUT' => $client->put($endpoint, $payload),
                'PATCH' => $client->patch($endpoint, $payload),
                default => $client->post($endpoint, $payload),
            };
        } catch (ConnectionException $e) {
            Log::warning('communication_center.marketplace_api.connection_failed', [
                'action' => 'sendReply',
                'marketplace' => $this->marketplaceCode(),
                'store_id' => $store->id,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'connection_failed'];
        }

        if (!$response->successful()) {
            $this->logFailedCall('sendReply', $store, $response->status(), $response->body());
            return ['ok' => false, 'status' => $response->status()];
        }

        $json = $response->json();
        return [
            'ok' => true,
            'status' => $response->status(),
            'external_message_id' => data_get($json, 'id')
                ?? data_get($json, 'messageId')
                ?? data_get($json, 'external_message_id'),
            'external_thread_id' => $externalThreadId,
            'raw' => is_array($json) ? $json : null,
        ];
    }

    protected function client(MarketplaceStore $store): ?PendingRequest
    {
        $creds = $this->credentials($store);
        $timeout = (int) Arr::get($creds, 'timeout', config('marketplaces.' . $this->marketplaceCode() . '.timeout', 20));
        $retry = (int) Arr::get($creds, 'retry', 2);
        $retrySleep = (int) Arr::get($creds, 'retry_sleep_ms', 300);

        $request = Http::retry($retry, $retrySleep)
            ->timeout($timeout)
            ->acceptJson()
            ->asJson();

        $apiKey = (string) Arr::get($creds, 'api_key', '');
        $apiSecret = (string) Arr::get($creds, 'api_secret', Arr::get($creds, 'secret', ''));
        $token = (string) Arr::get($creds, 'access_token', Arr::get($creds, 'token', ''));
        $authType = strtolower((string) Arr::get($creds, 'auth_type', 'auto'));

        if (($authType === 'bearer' || $authType === 'auto') && $token !== '') {
            $request = $request->withToken($token);
        } elseif (($authType === 'basic' || $authType === 'auto') && $apiKey !== '' && $apiSecret !== '') {
            $request = $request->withBasicAuth($apiKey, $apiSecret);
        } elseif ($authType === 'header' && $apiKey !== '') {
            $headerKey = (string) Arr::get($creds, 'api_key_header', 'X-API-KEY');
            $request = $request->withHeaders([$headerKey => $apiKey]);
        } else {
            return null;
        }

        $headers = Arr::get($creds, 'headers', []);
        if (is_array($headers) && $headers !== []) {
            $request = $request->withHeaders($headers);
        }

        return $request;
    }

    protected function resolveEndpoint(MarketplaceStore $store, string $key, string $fallbackPath, array $replace = []): string
    {
        $creds = $this->credentials($store);

        $direct = trim((string) Arr::get($creds, $key, ''));
        if ($direct === '') {
            $configured = config('marketplaces.' . $this->marketplaceCode() . '.communication.' . $key);
            $direct = trim((string) ($configured ?? ''));
        }

        if ($direct === '') {
            $baseUrl = trim((string) Arr::get($creds, 'base_url', config('marketplaces.' . $this->marketplaceCode() . '.base_url', '')));
            if ($baseUrl === '') {
                return '';
            }
            $direct = rtrim($baseUrl, '/') . $fallbackPath;
        } elseif (!preg_match('#^https?://#i', $direct)) {
            $baseUrl = trim((string) Arr::get($creds, 'base_url', config('marketplaces.' . $this->marketplaceCode() . '.base_url', '')));
            if ($baseUrl !== '') {
                $direct = rtrim($baseUrl, '/') . '/' . ltrim($direct, '/');
            } else {
                return '';
            }
        }

        $placeholders = array_merge([
            'store_external_id' => (string) ($store->store_external_id ?? ''),
            'marketplace' => $this->marketplaceCode(),
        ], $replace);

        foreach ($placeholders as $ph => $value) {
            $direct = str_replace('{' . $ph . '}', (string) $value, $direct);
        }

        return $direct;
    }

    protected function credentials(MarketplaceStore $store): array
    {
        $creds = $store->credentials;
        return is_array($creds) ? $creds : [];
    }

    /** @return array<int, mixed> */
    protected function extractRows(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        foreach (['threads', 'messages', 'items', 'content', 'data', 'results'] as $key) {
            $rows = $payload[$key] ?? null;
            if (is_array($rows)) {
                return array_is_list($rows) ? $rows : [$rows];
            }
        }

        return [$payload];
    }

    /** @return array<string, mixed> */
    protected function mapThreadRow(array $row): array
    {
        return [
            'external_thread_id' => (string) ($row['external_thread_id'] ?? $row['threadId'] ?? $row['id'] ?? ''),
            'subject' => (string) ($row['subject'] ?? $row['title'] ?? ''),
            'product_sku' => (string) ($row['product_sku'] ?? $row['sku'] ?? ''),
            'product_name' => (string) ($row['product_name'] ?? $row['productName'] ?? $row['itemName'] ?? ''),
            'customer_name' => (string) ($row['customer_name'] ?? $row['customerName'] ?? ''),
            'customer_external_id' => (string) ($row['customer_external_id'] ?? $row['customerId'] ?? ''),
            'status' => $this->normalizeStatus((string) ($row['status'] ?? 'open')),
            'last_inbound_at' => $row['last_inbound_at'] ?? $row['lastMessageAt'] ?? $row['updatedAt'] ?? null,
            'due_at' => $row['due_at'] ?? $row['deadline'] ?? null,
            'meta' => is_array($row['meta'] ?? null) ? $row['meta'] : $row,
        ];
    }

    /** @return array<string, mixed> */
    protected function mapMessageRow(array $row): array
    {
        $direction = strtolower((string) ($row['direction'] ?? $row['messageType'] ?? 'inbound'));
        if (!in_array($direction, ['inbound', 'outbound'], true)) {
            $direction = in_array($direction, ['incoming', 'customer', 'buyer'], true) ? 'inbound' : 'outbound';
        }

        return [
            'direction' => $direction,
            'body' => (string) ($row['body'] ?? $row['message'] ?? $row['text'] ?? ''),
            'created_at_external' => $row['created_at_external'] ?? $row['createdAt'] ?? $row['date'] ?? null,
            'sender_type' => $this->normalizeSenderType((string) ($row['sender_type'] ?? $row['senderType'] ?? 'customer')),
            'meta' => is_array($row['meta'] ?? null) ? $row['meta'] : $row,
        ];
    }

    protected function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        return match ($status) {
            'pending', 'beklemede' => 'pending',
            'answered', 'replied', 'yanitlandi', 'yanıtlandı' => 'answered',
            'closed', 'resolved', 'kapali', 'kapalı' => 'closed',
            'overdue', 'expired', 'gecikmis', 'gecikmiş' => 'overdue',
            default => 'open',
        };
    }

    protected function normalizeSenderType(string $senderType): string
    {
        $senderType = strtolower(trim($senderType));
        return match ($senderType) {
            'seller', 'merchant', 'admin' => 'seller',
            'system', 'bot' => 'system',
            default => 'customer',
        };
    }

    protected function logFailedCall(string $action, MarketplaceStore $store, int $status, string $body): void
    {
        Log::warning('communication_center.marketplace_api.failed', [
            'action' => $action,
            'marketplace' => $this->marketplaceCode(),
            'store_id' => $store->id,
            'store_external_id' => $store->store_external_id,
            'status' => $status,
            'body' => mb_substr($body, 0, 2000),
        ]);
    }
}
