<?php

namespace App\Domains\Marketplaces\Connectors\Hepsiburada;

use App\Domains\Marketplaces\Contracts\AuthHeaderBuilderInterface;

class HepsiburadaAuthHeaderBuilder implements AuthHeaderBuilderInterface
{
    public function headers(array $credentials): array
    {
        // TODO: Replace with real Hepsiburada token/signature flow.
        return [
            'Authorization' => 'Bearer ' . (string) ($credentials['access_token'] ?? ''),
            'Content-Type' => 'application/json',
        ];
    }
}

