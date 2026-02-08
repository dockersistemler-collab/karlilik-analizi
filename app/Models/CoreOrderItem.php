<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'marketplace',
        'order_id',
        'order_item_id',
        'order_date',
        'ship_date',
        'delivered_date',
        'sku',
        'product_id',
        'variant',
        'quantity',
        'currency',
        'fx_rate',
        'gross_sales',
        'discounts',
        'refunds',
        'net_sales',
        'commission_fee',
        'payment_fee',
        'shipping_fee',
        'other_fees',
        'fees_total',
        'vat_amount',
        'tax_amount',
        'cogs_unit',
        'cogs_total',
        'gross_profit',
        'contribution_margin',
        'net_profit',
        'status',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'ship_date' => 'datetime',
        'delivered_date' => 'datetime',
        'quantity' => 'integer',
        'fx_rate' => 'decimal:6',
        'gross_sales' => 'decimal:2',
        'discounts' => 'decimal:2',
        'refunds' => 'decimal:2',
        'net_sales' => 'decimal:2',
        'commission_fee' => 'decimal:2',
        'payment_fee' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'other_fees' => 'decimal:2',
        'fees_total' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'cogs_unit' => 'decimal:2',
        'cogs_total' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'contribution_margin' => 'decimal:2',
        'net_profit' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
