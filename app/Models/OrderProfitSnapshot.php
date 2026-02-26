<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProfitSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'marketplace',
        'order_id',
        'gross_revenue',
        'product_cost',
        'commission_amount',
        'shipping_amount',
        'service_amount',
        'campaign_amount',
        'ad_amount',
        'packaging_amount',
        'operational_amount',
        'return_risk_amount',
        'other_cost_amount',
        'net_profit',
        'net_margin',
        'calculation_version',
        'calculated_at',
        'meta',
    ];

    protected $casts = [
        'gross_revenue' => 'decimal:4',
        'product_cost' => 'decimal:4',
        'commission_amount' => 'decimal:4',
        'shipping_amount' => 'decimal:4',
        'service_amount' => 'decimal:4',
        'campaign_amount' => 'decimal:4',
        'ad_amount' => 'decimal:4',
        'packaging_amount' => 'decimal:4',
        'operational_amount' => 'decimal:4',
        'return_risk_amount' => 'decimal:4',
        'other_cost_amount' => 'decimal:4',
        'net_profit' => 'decimal:4',
        'net_margin' => 'decimal:4',
        'calculated_at' => 'datetime',
        'meta' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

