<?php

namespace App\Domains\Marketplaces\Connectors\Hepsiburada;

use App\Domains\Marketplaces\Connectors\BaseMockConnector;

class HepsiburadaMockConnector extends BaseMockConnector
{
    protected function marketplaceCode(): string
    {
        return 'hepsiburada';
    }
}

