<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'marketplace',
        'campaign_id',
        'name',
        'start_date',
        'end_date',
        'source',
        'meta',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'meta' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(MarketplaceCampaignItem::class, 'campaign_id');
    }
}

