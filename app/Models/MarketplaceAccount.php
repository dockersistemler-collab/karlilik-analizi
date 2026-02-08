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
        'marketplace',
        'store_name',
        'credentials',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
