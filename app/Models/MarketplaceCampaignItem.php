<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceCampaignItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'sku',
        'discount_rate',
        'meta',
    ];

    protected $casts = [
        'discount_rate' => 'decimal:4',
        'meta' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(MarketplaceCampaign::class, 'campaign_id');
    }
}

