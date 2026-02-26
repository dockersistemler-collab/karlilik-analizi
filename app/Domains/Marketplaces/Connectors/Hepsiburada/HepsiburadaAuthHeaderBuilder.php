<?php

namespace App\Domains\Marketplaces\Connectors\Hepsiburada;

use App\Domains\Marketplaces\Contracts\AuthHeaderBuilderInterface;
use RuntimeException;

class HepsiburadaAuthHeaderBuilder implements AuthHeaderBuilderInterface
{
    public function headers(array $credentials): array
    {
        $accessToken = (string) ($credentials['access_token'] ?? $credentials['accessToken'] ?? $credentials['token'] ?? '');
        $username = (string) ($credentials['username'] ?? $credentials['merchant_id'] ?? '');
        $password = (string) ($credentials['password'] ?? $credentials['merchant_password'] ?? '');

        if ($accessToken !== '') {
            $authorization = 'Bearer ' . $accessToken;
        } elseif ($username !== '' && $password !== '') {
            $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        } else {
            throw new RuntimeException('Hepsiburada credentials missing: access_token or username/password.');
        }

        $headers = [
            'Authorization' => $authorization,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($username !== '') {
            $headers['User-Agent'] = 'HB-Merchant/' . $username;
        }

        return $headers;
    }
}
