<?php

namespace App\Domains\Marketplaces\Connectors\Trendyol;

use App\Domains\Marketplaces\Contracts\AuthHeaderBuilderInterface;

class TrendyolAuthHeaderBuilder implements AuthHeaderBuilderInterface
{
    public function headers(array $credentials): array
    {
        // TODO: Replace with real Trendyol auth/signature strategy.
        $apiKey = (string) ($credentials['api_key'] ?? '');
        $apiSecret = (string) ($credentials['api_secret'] ?? '');
        $sellerId = (string) ($credentials['seller_id'] ?? '');

        $token = base64_encode($apiKey . ':' . $apiSecret);

        return [
            'Authorization' => 'Basic ' . $token,
            'User-Agent' => $sellerId !== '' ? "{$sellerId} - SelfIntegration" : 'SelfIntegration',
            'storeFrontCode' => (string) ($credentials['store_front_code'] ?? ''),
            'Content-Type' => 'application/json',
        ];
    }
}
