<?php

namespace App\Domains\Marketplaces\Services;

use App\Domains\Marketplaces\Connectors\Amazon\AmazonConnector;
use App\Domains\Marketplaces\Connectors\Amazon\AmazonMockConnector;
use App\Domains\Marketplaces\Connectors\Hepsiburada\HepsiburadaConnector;
use App\Domains\Marketplaces\Connectors\Hepsiburada\HepsiburadaMockConnector;
use App\Domains\Marketplaces\Connectors\N11\N11Connector;
use App\Domains\Marketplaces\Connectors\N11\N11MockConnector;
use App\Domains\Marketplaces\Connectors\Trendyol\TrendyolConnector;
use App\Domains\Marketplaces\Connectors\Trendyol\TrendyolMockConnector;
use App\Domains\Marketplaces\Contracts\MarketplaceConnectorInterface;
use App\Models\MarketplaceAccount;
use InvalidArgumentException;

class MarketplaceConnectorRegistry
{
    public function __construct(
        private readonly MarketplaceHttpClient $httpClient,
        private readonly SyncLogService $syncLogService,
        private readonly SensitiveValueMasker $masker,
    ) {
    }

    public function resolve(MarketplaceAccount $account, ?int $syncJobId = null): MarketplaceConnectorInterface
    {
        $code = strtolower((string) ($account->connector_key ?: $account->marketplace));
        $useMock = (bool) config('marketplaces.use_mock_connectors', false);

        if ($useMock) {
            return match ($code) {
                'trendyol' => new TrendyolMockConnector($account),
                'hepsiburada' => new HepsiburadaMockConnector($account),
                'n11' => new N11MockConnector($account),
                'amazon' => new AmazonMockConnector($account),
                default => throw new InvalidArgumentException("Unsupported marketplace connector: {$code}"),
            };
        }

        return match ($code) {
            'trendyol' => new TrendyolConnector($account, $this->httpClient, $this->syncLogService, $this->masker, $syncJobId),
            'hepsiburada' => new HepsiburadaConnector($account, $this->httpClient, $this->syncLogService, $this->masker, $syncJobId),
            'n11' => new N11Connector($account, $this->httpClient, $this->syncLogService, $this->masker, $syncJobId),
            'amazon' => new AmazonConnector($account, $this->httpClient, $this->syncLogService, $this->masker, $syncJobId),
            default => throw new InvalidArgumentException("Unsupported marketplace connector: {$code}"),
        };
    }
}
