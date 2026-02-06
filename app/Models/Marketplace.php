<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marketplace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'api_url',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    // İlişkiler
    public function credentials()
    {
        // Bir Pazaryerine (örn: Trendyol) baÄŸlı BİRDEN Ã‡OK satıcı hesabı olabilir.
        return $this->hasMany(MarketplaceCredential::class);
    }

    public function products()
    {
        return $this->hasMany(MarketplaceProduct::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
