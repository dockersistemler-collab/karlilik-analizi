<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceCarrierMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_code',
        'external_carrier_code',
        'provider_key',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (MarketplaceCarrierMapping $mapping) {
            $mapping->external_carrier_code_normalized = \App\Support\Cargo\CarrierNormalizer::normalizeCarrier(
                $mapping->external_carrier_code
            );
        });
    }
}
