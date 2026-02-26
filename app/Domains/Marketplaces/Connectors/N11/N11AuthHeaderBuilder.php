<?php

namespace App\Domains\Marketplaces\Connectors\N11;

use App\Domains\Marketplaces\Contracts\AuthHeaderBuilderInterface;
use RuntimeException;

class N11AuthHeaderBuilder implements AuthHeaderBuilderInterface
{
    public function headers(array $credentials): array
    {
        $accessToken = (string) ($credentials['access_token'] ?? $credentials['accessToken'] ?? '');
        $appKey = (string) ($credentials['app_key'] ?? $credentials['api_key'] ?? '');
        $appSecret = (string) ($credentials['app_secret'] ?? $credentials['api_secret'] ?? '');

        if ($accessToken !== '') {
            $authorization = 'Bearer ' . $accessToken;
        } elseif ($appKey !== '' && $appSecret !== '') {
            $authorization = 'Basic ' . base64_encode($appKey . ':' . $appSecret);
        } else {
            throw new RuntimeException('N11 credentials missing: access_token or app_key/app_secret.');
        }

        $headers = [
            'Authorization' => $authorization,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($appKey !== '') {
            $headers['appkey'] = $appKey;
        }

        return $headers;
    }
}
