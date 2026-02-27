<?php

namespace App\Services\Marketplaces\Clients;

class HepsiburadaClient extends AbstractStubClient
{
    protected function marketplaceCode(): string
    {
        return 'hepsiburada';
    }
}
