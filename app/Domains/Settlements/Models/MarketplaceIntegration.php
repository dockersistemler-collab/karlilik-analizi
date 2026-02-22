<?php

namespace App\Domains\Settlements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}

