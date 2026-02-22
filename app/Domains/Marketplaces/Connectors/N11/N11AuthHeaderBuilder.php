<?php

namespace App\Domains\Marketplaces\Connectors\N11;

use App\Domains\Marketplaces\Contracts\AuthHeaderBuilderInterface;

class N11AuthHeaderBuilder implements AuthHeaderBuilderInterface
{
    public function headers(array $credentials): array
    {
        // TODO: Replace with real N11 auth strategy.
        return [
            'Authorization' => 'Bearer ' . (string) ($credentials['access_token'] ?? ''),
            'Content-Type' => 'application/json',
        ];
    }
}

