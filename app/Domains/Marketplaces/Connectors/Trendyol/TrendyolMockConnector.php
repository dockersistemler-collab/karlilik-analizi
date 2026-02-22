<?php

namespace App\Domains\Marketplaces\Connectors\Trendyol;

use App\Domains\Marketplaces\Connectors\BaseMockConnector;

class TrendyolMockConnector extends BaseMockConnector
{
    protected function marketplaceCode(): string
    {
        return 'trendyol';
    }
}

