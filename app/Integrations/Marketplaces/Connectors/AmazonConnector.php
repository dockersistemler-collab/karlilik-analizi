<?php

namespace App\Integrations\Marketplaces\Connectors;

use App\Integrations\Marketplaces\Contracts\MarketplaceConnectorInterface;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceListing;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonConnector implements MarketplaceConnectorInterface
{
    public function testConnection(MarketplaceAccount $account): array
    {
        $creds = $this->credentials($account);
        $client = $this->client($creds);
        $url = $this->resolveEndpoint($creds, 'healthcheck_url', '/orders');

        if (!$url || !$client) {
            return ['ok' => false, 'error' => 'missing_credentials'];
        }

        $response = $client->get($url, ['limit' => 1]);

        return ['ok' => $response->successful(), 'status' => $response->status()];
    }

    public function pullListings(MarketplaceAccount $account): array
    {
        $creds = $this->credentials($account);
        $client = $this->client($creds);
        $url = $this->resolveEndpoint($creds, 'listings_url', '/listings');

        if (!$url || !$client) {
            return [];
        }

        $response = $client->get($url, ['limit' => 200]);
        if (!$response->successful()) {
            return [];
        }

        $payload = $response->json();
        return is_array($payload) ? ($payload['items'] ?? $payload['content'] ?? $payload) : [];
    }

    public function pullOrders(MarketplaceAccount $account, ?string $since = null): array
    {
        $creds = $this->credentials($account);
        $client = $this->client($creds);
        $url = $this->resolveEndpoint($creds, 'orders_url', '/orders');

        if (!$url || !$client) {
            return [];
        }

        $query = ['limit' => 200];
        if ($since) {
            $query['createdAfter'] = $since;
        }

        $response = $client->get($url, $query);
        if (!$response->successful()) {
            return [];
        }

        $payload = $response->json();
        return is_array($payload) ? ($payload['orders'] ?? $payload['items'] ?? $payload) : [];
    }

    public function updateStock(MarketplaceListing $listing, int $quantity): array
    {
        $account = $listing->account;
        if (!$account) {
            return ['ok' => false, 'error' => 'missing_account'];
        }

        $creds = $this->credentials($account);
        $client = $this->client($creds);
        $url = $this->resolveEndpoint($creds, 'stock_update_url', '/listings/stock');

        if (!$url || !$client) {
            return ['ok' => false, 'error' => 'missing_credentials'];
        }

        $payload = [
            'listingId' => $listing->external_listing_id,
            'sku' => $listing->external_sku,
            'barcode' => $listing->external_barcode,
            'quantity' => max(0, $quantity),
        ];

        $method = strtoupper((string) Arr::get($creds, 'stock_update_method', 'POST'));
        $response = $method === 'PATCH'
            ? $client->patch($url, $payload)
            : ($method === 'PUT' ? $client->put($url, $payload) : $client->post($url, $payload));

        if (!$response->successful()) {
            Log::warning('inventory.connector.amazon.update_stock.failed', [
                'listing_id' => $listing->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return ['ok' => $response->successful(), 'status' => $response->status(), 'provider' => 'amazon'];
    }

    private function credentials(MarketplaceAccount $account): array
    {
        $creds = $account->credentials_json;
        if (!is_array($creds) || $creds === []) {
            $creds = $account->credentials;
        }

        return is_array($creds) ? $creds : [];
    }

    private function client(array $creds): ?PendingRequest
    {
        $request = Http::retry(2, 300)->timeout(30)->acceptJson();

        $accessToken = (string) Arr::get($creds, 'access_token', '');
        if ($accessToken !== '') {
            return $request->withToken($accessToken);
        }

        $token = (string) Arr::get($creds, 'token', '');
        if ($token !== '') {
            return $request->withToken($token);
        }

        $apiKey = (string) Arr::get($creds, 'api_key', '');
        $apiSecret = (string) Arr::get($creds, 'api_secret', '');
        if ($apiKey !== '' && $apiSecret !== '') {
            return $request->withBasicAuth($apiKey, $apiSecret);
        }

        return null;
    }

    private function resolveEndpoint(array $creds, string $key, string $fallbackPath): string
    {
        $direct = trim((string) Arr::get($creds, $key, ''));
        if ($direct !== '') {
            return $direct;
        }

        $baseUrl = trim((string) Arr::get($creds, 'base_url', ''));
        if ($baseUrl === '') {
            return '';
        }

        return rtrim($baseUrl, '/') . $fallbackPath;
    }
}
