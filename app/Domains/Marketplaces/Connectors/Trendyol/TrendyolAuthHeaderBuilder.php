<?php

namespace App\Domains\Marketplaces\Connectors\Trendyol;

use App\Domains\Marketplaces\Contracts\AuthHeaderBuilderInterface;
use RuntimeException;

class TrendyolAuthHeaderBuilder implements AuthHeaderBuilderInterface
{
    public function headers(array $credentials): array
    {
        $apiKey = (string) ($credentials['api_key'] ?? $credentials['client_id'] ?? '');
        $apiSecret = (string) ($credentials['api_secret'] ?? $credentials['client_secret'] ?? '');
        $sellerId = (string) ($credentials['seller_id'] ?? '');
        $storeFrontCode = (string) ($credentials['store_front_code'] ?? '');

        if ($apiKey === '' || $apiSecret === '') {
            throw new RuntimeException('Trendyol credentials missing: api_key/api_secret.');
        }

        $token = base64_encode($apiKey . ':' . $apiSecret);

        $headers = [
            'Authorization' => 'Basic ' . $token,
            'User-Agent' => $sellerId !== '' ? "{$sellerId} - SelfIntegration" : 'SelfIntegration',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($storeFrontCode !== '') {
            $headers['storeFrontCode'] = $storeFrontCode;
        }

        return $headers;
    }
}
