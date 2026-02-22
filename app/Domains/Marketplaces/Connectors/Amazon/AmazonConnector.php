<?php

namespace App\Domains\Marketplaces\Connectors\Amazon;

use App\Domains\Marketplaces\Connectors\BaseRealConnector;
use App\Domains\Marketplaces\Contracts\MarketplaceConnectorInterface;
use DateTimeInterface;

class AmazonConnector extends BaseRealConnector implements MarketplaceConnectorInterface
{
    public function fetchOrders(
        DateTimeInterface $from,
        DateTimeInterface $to,
        array $filters = [],
        ?int $page = 0,
        ?int $size = 200
    ): array
    {
        // TODO: implement real Amazon SP-API orders endpoint integration.
        return ['items' => [], 'next_page_token' => null];
    }

    public function fetchReturns(string $from, string $to, ?string $pageToken = null): array
    {
        // TODO: implement real Amazon returns endpoint integration.
        return ['items' => [], 'next_page_token' => null];
    }

    public function fetchPayouts(string $from, string $to, ?string $pageToken = null): array
    {
        // TODO: implement real Amazon settlements endpoint integration.
        return ['items' => [], 'next_page_token' => null];
    }

    public function fetchPayoutTransactions(string $payoutReference, ?string $pageToken = null): array
    {
        // TODO: implement real Amazon settlement transaction endpoint integration.
        return ['items' => [], 'next_page_token' => null];
    }
}
