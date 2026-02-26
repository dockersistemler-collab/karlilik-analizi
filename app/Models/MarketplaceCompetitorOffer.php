<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceCompetitorOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_id',
        'seller_name',
        'price',
        'shipping_speed',
        'store_score',
        'is_featured',
        'meta',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'store_score' => 'decimal:4',
        'is_featured' => 'boolean',
        'meta' => 'array',
    ];

    public function snapshot()
    {
        return $this->belongsTo(MarketplaceOfferSnapshot::class, 'snapshot_id');
    }
}

