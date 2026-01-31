<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'marketplace_id',
        'external_id',
        'parent_external_id',
        'name',
        'path',
        'is_leaf',
        'raw',
        'synced_at',
    ];

    protected $casts = [
        'raw' => 'array',
        'is_leaf' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }
}

