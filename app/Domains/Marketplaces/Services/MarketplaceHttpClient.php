<?php

namespace App\Domains\Marketplaces\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class MarketplaceHttpClient
{
    public function build(string $baseUrl, int $timeoutSeconds = 20): PendingRequest
    {
        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->timeout($timeoutSeconds)
            ->retry(3, 500, throw: false);
    }
}

