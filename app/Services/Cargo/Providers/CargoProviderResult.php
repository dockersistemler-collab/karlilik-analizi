<?php

namespace App\Services\Cargo\Providers;

class CargoProviderResult
{
    /**
     * @param array<string,mixed>|null $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $status = null,
        public readonly ?array $raw = null,
    ) {
    }
}
