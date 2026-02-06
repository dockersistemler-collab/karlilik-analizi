<?php

namespace App\Events;

class WebhookSignatureInvalid
{
    public function __construct(
        public readonly int $tenantId,
        public readonly ?string $source,
        public readonly string $reason
    ) {
    }
}