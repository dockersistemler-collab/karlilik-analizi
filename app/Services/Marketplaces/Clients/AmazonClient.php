<?php

namespace App\Services\Marketplaces\Clients;

class AmazonClient extends AbstractStubClient
{
    protected function marketplaceCode(): string
    {
        return 'amazon';
    }
}
