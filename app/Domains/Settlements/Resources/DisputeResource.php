<?php

namespace App\Domains\Settlements\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payout_id' => $this->payout_id,
            'dispute_type' => $this->dispute_type,
            'expected_amount' => (float) $this->expected_amount,
            'actual_amount' => (float) $this->actual_amount,
            'diff_amount' => (float) $this->diff_amount,
            'status' => $this->status,
            'assigned_user_id' => $this->assigned_user_id,
            'evidence' => $this->evidence,
            'notes' => $this->notes,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

