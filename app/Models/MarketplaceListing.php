<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'marketplace_account_id',
        'product_id',
        'external_listing_id',
        'external_sku',
        'external_barcode',
        'sync_enabled',
        'last_known_market_stock',
    ];

    protected $casts = [
        'sync_enabled' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function account()
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
