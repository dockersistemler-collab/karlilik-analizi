<?php

namespace App\Domains\Marketplaces\Connectors\Amazon;

use App\Domains\Marketplaces\Contracts\AuthHeaderBuilderInterface;
use RuntimeException;

class AmazonAuthHeaderBuilder implements AuthHeaderBuilderInterface
{
    public function headers(array $credentials): array
    {
        $accessToken = (string) ($credentials['access_token'] ?? $credentials['lwa_access_token'] ?? '');
        if ($accessToken === '') {
            throw new RuntimeException('Amazon credentials missing: access_token/lwa_access_token.');
        }

        return [
            'Authorization' => 'Bearer ' . $accessToken,
            'x-amz-access-token' => $accessToken,
            'x-amz-date' => gmdate('Ymd\THis\Z'),
            'User-Agent' => 'PazarSync/1.0',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
