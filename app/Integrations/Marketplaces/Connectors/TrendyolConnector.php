<?php

namespace App\Integrations\Marketplaces\Connectors;

use App\Integrations\Marketplaces\Contracts\MarketplaceConnectorInterface;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceListing;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendyolConnector implements MarketplaceConnectorInterface
{
    public function testConnection(MarketplaceAccount $account): array
    {
        $creds = $this->credentials($account);
        $supplierId = (string) Arr::get($creds, 'supplier_id', '');
        $apiKey = (string) Arr::get($creds, 'api_key', '');
        $apiSecret = (string) Arr::get($creds, 'api_secret', '');
        $baseUrl = $this->resolveBaseUrl($creds);

        if ($supplierId === '' || $apiKey === '' || $apiSecret === '' || $baseUrl === '') {
            return ['ok' => false, 'error' => 'missing_credentials'];
        }

        $response = Http::retry(2, 300)
            ->timeout(20)
            ->acceptJson()
            ->withBasicAuth($apiKey, $apiSecret)
            ->get(rtrim($baseUrl, '/') . '/' . $supplierId . '/products', [
                'page' => 0,
                'size' => 1,
            ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
        ];
    }

    public function pullListings(MarketplaceAccount $account): array
    {
        $creds = $this->credentials($account);
        $supplierId = (string) Arr::get($creds, 'supplier_id', '');
        $apiKey = (string) Arr::get($creds, 'api_key', '');
        $apiSecret = (string) Arr::get($creds, 'api_secret', '');
        $baseUrl = $this->resolveBaseUrl($creds);

        if ($supplierId === '' || $apiKey === '' || $apiSecret === '' || $baseUrl === '') {
            return [];
        }

        $response = Http::retry(2, 300)
            ->timeout(30)
            ->acceptJson()
            ->withBasicAuth($apiKey, $apiSecret)
            ->get(rtrim($baseUrl, '/') . '/' . $supplierId . '/products', [
                'page' => 0,
                'size' => 200,
            ]);

        if (!$response->successful()) {
            Log::warning('inventory.connector.trendyol.pull_listings.failed', [
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);

            return [];
        }

        $payload = $response->json();
        return is_array($payload) ? ($payload['content'] ?? $payload['items'] ?? $payload) : [];
    }

    public function pullOrders(MarketplaceAccount $account, ?string $since = null): array
    {
        $creds = $this->credentials($account);
        $supplierId = (string) Arr::get($creds, 'supplier_id', '');
        $apiKey = (string) Arr::get($creds, 'api_key', '');
        $apiSecret = (string) Arr::get($creds, 'api_secret', '');
        $baseUrl = $this->resolveBaseUrl($creds);

        if ($supplierId === '' || $apiKey === '' || $apiSecret === '' || $baseUrl === '') {
            return [];
        }

        $query = [
            'page' => 0,
            'size' => 200,
        ];
        if ($since) {
            $query['startDate'] = strtotime($since) * 1000;
            $query['endDate'] = now()->timestamp * 1000;
        }

        $response = Http::retry(2, 300)
            ->timeout(30)
            ->acceptJson()
            ->withBasicAuth($apiKey, $apiSecret)
            ->get(rtrim($baseUrl, '/') . '/' . $supplierId . '/orders', $query);

        if (!$response->successful()) {
            Log::warning('inventory.connector.trendyol.pull_orders.failed', [
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);

            return [];
        }

        $payload = $response->json();
        return is_array($payload) ? ($payload['content'] ?? $payload['items'] ?? $payload) : [];
    }

    public function updateStock(MarketplaceListing $listing, int $quantity): array
    {
        $account = $listing->account;
        if (!$account) {
            return ['ok' => false, 'error' => 'missing_account'];
        }

        $creds = $this->credentials($account);
        $supplierId = (string) Arr::get($creds, 'supplier_id', '');
        $apiKey = (string) Arr::get($creds, 'api_key', '');
        $apiSecret = (string) Arr::get($creds, 'api_secret', '');
        $baseUrl = $this->resolveBaseUrl($creds);

        $barcode = (string) ($listing->external_barcode ?: $listing->product?->barcode ?: '');
        if ($supplierId === '' || $apiKey === '' || $apiSecret === '' || $baseUrl === '' || $barcode === '') {
            return ['ok' => false, 'error' => 'missing_stock_update_fields'];
        }

        $payload = [
            'items' => [[
                'barcode' => $barcode,
                'quantity' => max(0, $quantity),
            ]],
        ];

        $response = Http::retry(2, 300)
            ->timeout(30)
            ->acceptJson()
            ->withBasicAuth($apiKey, $apiSecret)
            ->post(rtrim($baseUrl, '/') . '/' . $supplierId . '/products/price-and-inventory', $payload);

        if (!$response->successful()) {
            Log::warning('inventory.connector.trendyol.update_stock.failed', [
                'listing_id' => $listing->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'provider' => 'trendyol',
        ];
    }

    private function credentials(MarketplaceAccount $account): array
    {
        $creds = $account->credentials_json;
        if (!is_array($creds) || $creds === []) {
            $creds = $account->credentials;
        }

        return is_array($creds) ? $creds : [];
    }

    private function resolveBaseUrl(array $creds): string
    {
        $baseUrl = trim((string) Arr::get($creds, 'base_url', ''));
        if ($baseUrl !== '') {
            return rtrim($baseUrl, '/');
        }

        return Arr::get($creds, 'is_test', false)
            ? 'https://stageapi.trendyol.com/stage/sapigw/suppliers'
            : 'https://api.trendyol.com/sapigw/suppliers';
    }
}
