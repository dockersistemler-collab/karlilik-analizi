<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplacePriceHistory extends Model
{
    use HasFactory;

    protected $table = 'marketplace_price_history';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'marketplace',
        'sku',
        'date',
        'unit_price',
        'units_sold',
        'revenue',
        'is_promo_day',
        'is_shipping_shock',
        'is_fee_shock',
        'shock_flags',
        'promo_source',
        'promo_campaign_id',
    ];

    protected $casts = [
        'date' => 'date',
        'unit_price' => 'decimal:4',
        'revenue' => 'decimal:4',
        'is_promo_day' => 'boolean',
        'is_shipping_shock' => 'boolean',
        'is_fee_shock' => 'boolean',
        'shock_flags' => 'array',
    ];
}
