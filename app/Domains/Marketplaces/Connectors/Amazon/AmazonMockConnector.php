<?php

namespace App\Domains\Marketplaces\Connectors\Amazon;

use App\Domains\Marketplaces\Connectors\BaseMockConnector;

class AmazonMockConnector extends BaseMockConnector
{
    protected function marketplaceCode(): string
    {
        return 'amazon';
    }
}

