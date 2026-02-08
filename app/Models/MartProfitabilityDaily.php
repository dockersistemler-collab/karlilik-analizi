<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MartProfitabilityDaily extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'date',
        'marketplace',
        'net_sales',
        'fees_total',
        'cogs_total',
        'gross_profit',
        'contribution_margin',
        'net_profit',
        'orders_count',
        'items_count',
    ];

    protected $casts = [
        'date' => 'date',
        'net_sales' => 'decimal:2',
        'fees_total' => 'decimal:2',
        'cogs_total' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'contribution_margin' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'orders_count' => 'integer',
        'items_count' => 'integer',
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
