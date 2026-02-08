<?php

namespace App\Integrations\Marketplaces\DTO;

use Carbon\CarbonImmutable;

class AdjustmentDTO
{
    public function __construct(
        public string $marketplace,
        public string $orderItemId,
        public string $type,
        public float $amount,
        public string $currency,
        public CarbonImmutable $occurredAt,
        public array $meta = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'marketplace' => $this->marketplace,
            'order_item_id' => $this->orderItemId,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'occurred_at' => $this->occurredAt->toDateTimeString(),
            'meta' => $this->meta,
        ];
    }
}
