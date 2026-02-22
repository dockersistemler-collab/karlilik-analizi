<?php

namespace App\Domains\Marketplaces\Contracts;

interface AuthHeaderBuilderInterface
{
    public function headers(array $credentials): array;
}

