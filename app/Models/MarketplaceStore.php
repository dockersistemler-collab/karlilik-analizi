<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'marketplace_id',
        'store_name',
        'store_external_id',
        'credentials',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function threads()
    {
        return $this->hasMany(CommunicationThread::class, 'marketplace_store_id');
    }
}

