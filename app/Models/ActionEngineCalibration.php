<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionEngineCalibration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'marketplace',
        'sku',
        'window_days',
        'elasticity',
        'margin_uplift_factor',
        'ad_pause_revenue_drop_pct',
        'confidence',
        'diagnostics',
        'calculated_at',
    ];

    protected $casts = [
        'elasticity' => 'decimal:6',
        'margin_uplift_factor' => 'decimal:6',
        'ad_pause_revenue_drop_pct' => 'decimal:4',
        'confidence' => 'decimal:4',
        'diagnostics' => 'array',
        'calculated_at' => 'datetime',
    ];
}

