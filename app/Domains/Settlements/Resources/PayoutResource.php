<?php

namespace App\Domains\Settlements\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'marketplace_account_id' => $this->marketplace_account_id,
            'payout_reference' => $this->payout_reference,
            'period_start' => optional($this->period_start)->toDateString(),
            'period_end' => optional($this->period_end)->toDateString(),
            'expected_date' => optional($this->expected_date)->toDateString(),
            'expected_amount' => (float) $this->expected_amount,
            'paid_amount' => $this->paid_amount !== null ? (float) $this->paid_amount : null,
            'paid_date' => optional($this->paid_date)->toDateString(),
            'currency' => $this->currency,
            'status' => $this->status,
            'totals' => $this->totals,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

