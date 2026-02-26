<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyBoxScore extends Model
{
    use HasFactory;

    protected $table = 'buybox_scores';

    protected $fillable = [
        'tenant_id',
        'marketplace',
        'date',
        'sku',
        'buybox_score',
        'status',
        'win_probability',
        'drivers',
        'snapshot_id',
    ];

    protected $casts = [
        'date' => 'date',
        'win_probability' => 'decimal:4',
        'drivers' => 'array',
    ];

    public function snapshot()
    {
        return $this->belongsTo(MarketplaceOfferSnapshot::class, 'snapshot_id');
    }
}
