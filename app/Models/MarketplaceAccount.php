<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'marketplace_integration_id',
        'marketplace',
        'connector_key',
        'store_name',
        'credentials',
        'credentials_json',
        'status',
        'is_active',
        'last_synced_at',
        'last_sync_at',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'credentials_json' => 'encrypted:array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials',
        'credentials_json',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function integration()
    {
        return $this->belongsTo(\App\Domains\Settlements\Models\MarketplaceIntegration::class, 'marketplace_integration_id');
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function listings()
    {
        return $this->hasMany(MarketplaceListing::class);
    }
}
