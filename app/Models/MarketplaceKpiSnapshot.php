<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceKpiSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'marketplace',
        'date',
        'late_shipment_rate',
        'cancellation_rate',
        'return_rate',
        'performance_score',
        'rating_score',
        'odr',
        'valid_tracking_rate',
        'source',
        'meta',
    ];

    protected $casts = [
        'date' => 'date',
        'late_shipment_rate' => 'decimal:4',
        'cancellation_rate' => 'decimal:4',
        'return_rate' => 'decimal:4',
        'performance_score' => 'decimal:4',
        'rating_score' => 'decimal:4',
        'odr' => 'decimal:4',
        'valid_tracking_rate' => 'decimal:4',
        'meta' => 'array',
    ];
}

