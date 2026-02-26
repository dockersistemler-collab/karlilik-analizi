<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceFeeRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'marketplace',
        'sku',
        'category_id',
        'brand_id',
        'commission_rate',
        'fixed_fee',
        'shipping_fee',
        'service_fee',
        'campaign_contribution_rate',
        'vat_rate',
        'priority',
        'active',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:4',
        'fixed_fee' => 'decimal:4',
        'shipping_fee' => 'decimal:4',
        'service_fee' => 'decimal:4',
        'campaign_contribution_rate' => 'decimal:4',
        'vat_rate' => 'decimal:4',
        'active' => 'boolean',
    ];
}

