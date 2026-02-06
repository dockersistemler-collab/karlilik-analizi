<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'marketplace_id',
        'api_key',
        'api_secret',
        'supplier_id',
        'store_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'extra_credentials',
    ];

    protected $casts = [
        'extra_credentials' => 'array',
        'is_active' => 'boolean',
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'access_token',
        'refresh_token',
    ];

    // İlişkiler
    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function credential()
    {
    return $this->hasOne(MarketplaceCredential::class);
    }


}
