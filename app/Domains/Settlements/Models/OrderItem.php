<?php

namespace App\Domains\Settlements\Models;

use App\Domains\Tenancy\Concerns\BelongsToTenant;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'sku',
        'barcode',
        'shipment_package_id',
        'variant_id',
        'qty',
        'sale_price',
        'sale_vat',
        'cost_price',
        'cost_vat',
        'commission_amount',
        'commission_vat',
        'shipping_amount',
        'shipping_vat',
        'service_fee_amount',
        'service_fee_vat',
        'discounts',
        'penalties',
        'calculated',
        'raw_payload',
    ];

    protected $casts = [
        'sale_price' => 'decimal:4',
        'sale_vat' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'cost_vat' => 'decimal:4',
        'commission_amount' => 'decimal:4',
        'commission_vat' => 'decimal:4',
        'shipping_amount' => 'decimal:4',
        'shipping_vat' => 'decimal:4',
        'service_fee_amount' => 'decimal:4',
        'service_fee_vat' => 'decimal:4',
        'discounts' => 'array',
        'penalties' => 'array',
        'calculated' => 'array',
        'raw_payload' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
