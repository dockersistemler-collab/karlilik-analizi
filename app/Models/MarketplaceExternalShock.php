<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceExternalShock extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'marketplace',
        'sku',
        'date',
        'shock_type',
        'severity',
        'detected_by',
        'details',
    ];

    protected $casts = [
        'date' => 'date',
        'details' => 'array',
    ];
}

