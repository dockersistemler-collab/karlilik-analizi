<?php

namespace App\Domains\Marketplaces\Connectors\N11;

use App\Domains\Marketplaces\Connectors\BaseMockConnector;

class N11MockConnector extends BaseMockConnector
{
    protected function marketplaceCode(): string
    {
        return 'n11';
    }
}

