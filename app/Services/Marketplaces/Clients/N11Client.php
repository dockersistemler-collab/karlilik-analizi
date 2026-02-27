<?php

namespace App\Services\Marketplaces\Clients;

class N11Client extends AbstractStubClient
{
    protected function marketplaceCode(): string
    {
        return 'n11';
    }
}
