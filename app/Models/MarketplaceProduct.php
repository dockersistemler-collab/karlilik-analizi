<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'marketplace_id',
        'marketplace_product_id',
        'marketplace_sku',
        'price',
        'stock_quantity',
        'status',
        'rejection_reason',
        'commission_rate',
        'listing_url',
        'marketplace_data',
        'last_sync_at',
        'auto_sync',
    ];

    protected $casts = [
        'marketplace_data' => 'array',
        'last_sync_at' => 'datetime',
        'auto_sync' => 'boolean',
        'price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    // İlişkiler
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }
}
