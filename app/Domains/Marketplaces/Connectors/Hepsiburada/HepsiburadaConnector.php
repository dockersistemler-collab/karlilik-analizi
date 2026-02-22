<?php

namespace App\Domains\Marketplaces\Connectors\Hepsiburada;

use App\Domains\Marketplaces\Connectors\BaseRealConnector;
use App\Domains\Marketplaces\Contracts\MarketplaceConnectorInterface;
use DateTimeInterface;

class HepsiburadaConnector extends BaseRealConnector implements MarketplaceConnectorInterface
{
    public function fetchOrders(
        DateTimeInterface $from,
        DateTimeInterface $to,
        array $filters = [],
        ?int $page = 0,
        ?int $size = 200
    ): array
    {
        // TODO: implement real Hepsiburada orders endpoint + mapper contract.
        return ['items' => [], 'next_page_token' => null];
    }

    public function fetchReturns(string $from, string $to, ?string $pageToken = null): array
    {
        // TODO: implement real Hepsiburada returns endpoint.
        return ['items' => [], 'next_page_token' => null];
    }

    public function fetchPayouts(string $from, string $to, ?string $pageToken = null): array
    {
        // TODO: implement real Hepsiburada payout endpoint.
        return ['items' => [], 'next_page_token' => null];
    }

    public function fetchPayoutTransactions(string $payoutReference, ?string $pageToken = null): array
    {
        // TODO: implement real Hepsiburada payout transaction endpoint.
        return ['items' => [], 'next_page_token' => null];
    }
}
