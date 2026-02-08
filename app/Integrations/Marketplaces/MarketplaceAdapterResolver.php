<?php

namespace App\Integrations\Marketplaces;

use InvalidArgumentException;

class MarketplaceAdapterResolver
{
    public function resolve(string $marketplace): MarketplaceAdapterInterface
    {
        $normalized = strtolower(trim($marketplace));

        return match ($normalized) {
            'trendyol' => app(TrendyolAdapter::class),
            'hepsiburada', 'hb' => app(HepsiburadaAdapter::class),
            'n11' => app(N11Adapter::class),
            'amazon' => app(AmazonAdapter::class),
            default => throw new InvalidArgumentException("Marketplace adapter not found: {$marketplace}"),
        };
    }
}
