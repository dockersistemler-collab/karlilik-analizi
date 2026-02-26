<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceRiskScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'marketplace',
        'date',
        'risk_score',
        'status',
        'reasons',
    ];

    protected $casts = [
        'date' => 'date',
        'risk_score' => 'decimal:4',
        'reasons' => 'array',
    ];
}

