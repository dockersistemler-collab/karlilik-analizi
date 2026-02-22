<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'marketplace_integration_id',
        'marketplace_account_id',
        'marketplace_id',
        'marketplace_order_id',
        'order_number',
        'status',
        'total_amount',
        'commission_amount',
        'net_amount',
        'currency',
        'totals',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'billing_address',
        'cargo_company',
        'tracking_number',
        'shipment_provider_key',
        'shipment_status',
        'order_date',
        'approved_at',
        'shipped_at',
        'delivered_at',
        'items',
        'marketplace_data',
        'raw_payload',
    ];

    protected $casts = [
        'items' => 'array',
        'marketplace_data' => 'array',
        'totals' => 'array',
        'raw_payload' => 'array',
        'order_date' => 'datetime',
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    // İlişkiler
    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orderItems()
    {
        return $this->hasMany(\App\Domains\Settlements\Models\OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }
}

