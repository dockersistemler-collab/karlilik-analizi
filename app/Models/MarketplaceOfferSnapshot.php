<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceOfferSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'marketplace',
        'date',
        'sku',
        'listing_id',
        'is_winning',
        'position_rank',
        'our_price',
        'competitor_best_price',
        'competitor_count',
        'shipping_speed_score',
        'stock_available',
        'store_score',
        'rating_score',
        'promo_flag',
        'meta',
        'source',
    ];

    protected $casts = [
        'date' => 'date',
        'is_winning' => 'boolean',
        'promo_flag' => 'boolean',
        'our_price' => 'decimal:4',
        'competitor_best_price' => 'decimal:4',
        'shipping_speed_score' => 'decimal:4',
        'store_score' => 'decimal:4',
        'rating_score' => 'decimal:4',
        'meta' => 'array',
    ];

    public function competitorOffers()
    {
        return $this->hasMany(MarketplaceCompetitorOffer::class, 'snapshot_id');
    }
}

