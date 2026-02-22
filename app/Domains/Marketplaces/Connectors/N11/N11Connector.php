<?php

namespace App\Domains\Marketplaces\Connectors\N11;

use App\Domains\Marketplaces\Connectors\BaseRealConnector;
use App\Domains\Marketplaces\Contracts\MarketplaceConnectorInterface;
use DateTimeInterface;

class N11Connector extends BaseRealConnector implements MarketplaceConnectorInterface
{
    public function fetchOrders(
        DateTimeInterface $from,
        DateTimeInterface $to,
        array $filters = [],
        ?int $page = 0,
        ?int $size = 200
    ): array
    {
        // TODO: implement real N11 orders endpoint + pagination.
        return ['items' => [], 'next_page_token' => null];
    }

    public function fetchReturns(string $from, string $to, ?string $pageToken = null): array
    {
        // TODO: implement real N11 returns endpoint.
        return ['items' => [], 'next_page_token' => null];
    }

    public function fetchPayouts(string $from, string $to, ?string $pageToken = null): array
    {
        // TODO: implement real N11 payout endpoint.
        return ['items' => [], 'next_page_token' => null];
    }

    public function fetchPayoutTransactions(string $payoutReference, ?string $pageToken = null): array
    {
        // TODO: implement real N11 payout transaction endpoint.
        return ['items' => [], 'next_page_token' => null];
    }
}
