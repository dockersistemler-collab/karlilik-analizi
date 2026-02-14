<?php

namespace App\Integrations\Marketplaces;

use App\Integrations\Marketplaces\Connectors\AmazonConnector;
use App\Integrations\Marketplaces\Connectors\HepsiburadaConnector;
use App\Integrations\Marketplaces\Connectors\N11Connector;
use App\Integrations\Marketplaces\Connectors\TrendyolConnector;
use App\Integrations\Marketplaces\Contracts\MarketplaceConnectorInterface;
use InvalidArgumentException;

class ConnectorFactory
{
    public function make(string $key): MarketplaceConnectorInterface
    {
        return match (strtolower(trim($key))) {
            'trendyol' => app(TrendyolConnector::class),
            'hepsiburada' => app(HepsiburadaConnector::class),
            'n11' => app(N11Connector::class),
            'amazon' => app(AmazonConnector::class),
            default => throw new InvalidArgumentException("Unsupported inventory connector: {$key}"),
        };
    }
}
