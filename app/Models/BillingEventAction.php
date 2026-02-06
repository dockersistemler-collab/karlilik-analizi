<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class BillingEventAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_event_id',
        'action_type',
        'requested_by_admin_id',
        'status',
        'error_message',
        'correlation_id',
    ];

    public function billingEvent(): BelongsTo
    {
        return $this->belongsTo(BillingEvent::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_admin_id');
    }
}
