<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitCostProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'packaging_cost',
        'operational_cost',
        'return_rate_default',
        'ad_cost_default',
        'is_default',
    ];

    protected $casts = [
        'packaging_cost' => 'decimal:4',
        'operational_cost' => 'decimal:4',
        'return_rate_default' => 'decimal:4',
        'ad_cost_default' => 'decimal:4',
        'is_default' => 'boolean',
    ];
}

