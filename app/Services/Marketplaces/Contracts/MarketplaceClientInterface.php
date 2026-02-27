<?php

namespace App\Services\Marketplaces\Contracts;

use App\Models\MarketplaceStore;
use Carbon\CarbonInterface;

interface MarketplaceClientInterface
{
    public function fetchThreads(MarketplaceStore $store, string $channel, ?CarbonInterface $since = null): array;

    public function fetchThreadMessages(MarketplaceStore $store, string $externalThreadId): array;

    public function sendReply(MarketplaceStore $store, string $externalThreadId, string $message): array;
}

