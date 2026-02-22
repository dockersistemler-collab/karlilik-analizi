<?php

namespace App\Domains\Marketplaces\Connectors\Amazon;

use App\Domains\Marketplaces\Contracts\AuthHeaderBuilderInterface;

class AmazonAuthHeaderBuilder implements AuthHeaderBuilderInterface
{
    public function headers(array $credentials): array
    {
        // TODO: Replace with real SP-API signing process.
        return [
            'Authorization' => 'Bearer ' . (string) ($credentials['access_token'] ?? ''),
            'x-amz-access-token' => (string) ($credentials['access_token'] ?? ''),
            'Content-Type' => 'application/json',
        ];
    }
}

