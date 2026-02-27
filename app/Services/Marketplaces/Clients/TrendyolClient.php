<?php

namespace App\Services\Marketplaces\Clients;

class TrendyolClient extends AbstractStubClient
{
    protected function marketplaceCode(): string
    {
        return 'trendyol';
    }
}
