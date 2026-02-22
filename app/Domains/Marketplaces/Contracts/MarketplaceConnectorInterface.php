<?php

namespace App\Domains\Marketplaces\Contracts;

use DateTimeInterface;

interface MarketplaceConnectorInterface
{
    public function fetchOrders(
        DateTimeInterface $from,
        DateTimeInterface $to,
        array $filters = [],
        ?int $page = 0,
        ?int $size = 200
    ): array;

    public function fetchReturns(string $from, string $to, ?string $pageToken = null): array;

    public function fetchPayouts(string $from, string $to, ?string $pageToken = null): array;

    public function fetchPayoutTransactions(string $payoutReference, ?string $pageToken = null): array;
}
