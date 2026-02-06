<?php

namespace App\Services;

use App\Models\BillingEvent;
use App\Support\CorrelationId;

class BillingEventLogger
{
    /**
     * @param array<string,mixed> $data
     */
    public function record(array $data): BillingEvent
    {
        $data['correlation_id'] = $data['correlation_id'] ?? CorrelationId::current();

        return BillingEvent::create($data);
    }
}
