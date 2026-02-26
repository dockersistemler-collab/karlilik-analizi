<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceRiskProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'marketplace',
        'name',
        'weights',
        'thresholds',
        'metric_thresholds',
        'is_default',
    ];

    protected $casts = [
        'weights' => 'array',
        'thresholds' => 'array',
        'metric_thresholds' => 'array',
        'is_default' => 'boolean',
    ];
}

