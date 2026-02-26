<?php

namespace App\Domains\Settlements\Models;

use App\Domains\Tenancy\Concerns\BelongsToTenant;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LossFinding extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'reconciliation_id',
        'payout_id',
        'order_id',
        'code',
        'title',
        'detail',
        'severity',
        'amount',
        'confidence',
        'type',
        'suggested_dispute_type',
        'confidence_score',
        'pattern_key',
        'meta',
        'meta_json',
        'occurred_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'confidence' => 'integer',
        'confidence_score' => 'decimal:2',
        'meta' => 'array',
        'meta_json' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(Reconciliation::class);
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
