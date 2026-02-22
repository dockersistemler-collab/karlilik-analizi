<?php

namespace App\Domains\Settlements\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payout_id' => $this->payout_id,
            'type' => $this->type,
            'reference_id' => $this->reference_id,
            'amount' => (float) $this->amount,
            'vat_amount' => (float) $this->vat_amount,
            'meta' => $this->meta,
        ];
    }
}

