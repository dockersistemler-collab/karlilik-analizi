<?php

namespace App\Services\BuyBox;

use App\Services\BuyBox\Adapters\AmazonAdapter;
use App\Services\BuyBox\Adapters\HepsiburadaAdapter;
use App\Services\BuyBox\Adapters\MarketplaceAdapterInterface;
use App\Services\BuyBox\Adapters\N11Adapter;
use App\Services\BuyBox\Adapters\TrendyolAdapter;
use InvalidArgumentException;

class AdapterRegistry
{
    public function resolve(string $marketplace): MarketplaceAdapterInterface
    {
        $marketplace = strtolower(trim($marketplace));

        if (app()->bound("buybox.adapters.{$marketplace}")) {
            $adapter = app("buybox.adapters.{$marketplace}");
            if ($adapter instanceof MarketplaceAdapterInterface) {
                return $adapter;
            }
        }

        return match ($marketplace) {
            'trendyol' => app(TrendyolAdapter::class),
            'hepsiburada' => app(HepsiburadaAdapter::class),
            'amazon' => app(AmazonAdapter::class),
            'n11' => app(N11Adapter::class),
            default => throw new InvalidArgumentException("Unsupported marketplace: {$marketplace}"),
        };
    }
}

