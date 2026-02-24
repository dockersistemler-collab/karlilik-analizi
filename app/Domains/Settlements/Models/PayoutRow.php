<?php

namespace App\Domains\Settlements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_id',
        'order_no',
        'package_id',
        'type',
        'gross_amount',
        'vat_amount',
        'net_amount',
        'currency',
        'occurred_at',
        'raw',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'occurred_at' => 'datetime',
        'raw' => 'array',
    ];

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }
}

