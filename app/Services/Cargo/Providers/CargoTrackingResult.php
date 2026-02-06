<?php

namespace App\Services\Cargo\Providers;

class CargoTrackingResult
{
    /**
     * @param array<int,array<string,mixed>> $events
     * @param array<string,mixed>|null $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $status = null,
        public readonly array $events = [],
        public readonly ?array $raw = null,
        public readonly ?string $error = null,
    ) {
    }
}
