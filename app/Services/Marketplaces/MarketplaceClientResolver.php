<?php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceStore;
use App\Services\Marketplaces\Clients\AmazonClient;
use App\Services\Marketplaces\Clients\HepsiburadaClient;
use App\Services\Marketplaces\Clients\N11Client;
use App\Services\Marketplaces\Clients\TrendyolClient;
use App\Services\Marketplaces\Contracts\MarketplaceClientInterface;
use RuntimeException;

class MarketplaceClientResolver
{
    public function resolve(MarketplaceStore $store): MarketplaceClientInterface
    {
        $code = strtolower((string) ($store->marketplace?->code ?? ''));

        return match ($code) {
            'trendyol' => app(TrendyolClient::class),
            'hepsiburada' => app(HepsiburadaClient::class),
            'amazon' => app(AmazonClient::class),
            'n11' => app(N11Client::class),
            default => throw new RuntimeException('Unsupported marketplace for communication sync: ' . $code),
        };
    }
}

