<?php

namespace App\Services\EInvoices\Providers;

class ProviderResult
{
    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerInvoiceId = null,
        public readonly ?string $providerStatus = null,
        public readonly ?array $raw = null,
    ) {
    }
}

