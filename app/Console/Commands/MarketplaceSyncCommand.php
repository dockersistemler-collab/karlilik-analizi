<?php

namespace App\Console\Commands;

use App\Integrations\Marketplaces\Support\DateRangeFactory;
use App\Services\Marketplaces\MarketplaceSyncDispatcher;
use Illuminate\Console\Command;

class MarketplaceSyncCommand extends Command
{
    protected $signature = 'marketplaces:sync {--tenant=} {--marketplace=} {--range=last30days}';

    protected $description = 'Sync marketplace orders, returns, and fees for profitability.';

    public function handle(MarketplaceSyncDispatcher $dispatcher, DateRangeFactory $rangeFactory): int
    {
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;
        $marketplace = $this->option('marketplace') ? (string) $this->option('marketplace') : null;
        $rangeInput = (string) $this->option('range');

        $range = $rangeFactory->fromString($rangeInput);

        $dispatcher->dispatchAll($tenantId, $marketplace, $range);

        $this->info('Marketplace sync jobs dispatched.');

        return self::SUCCESS;
    }

}
