<?php

namespace App\Domains\Tenancy\Concerns;

use App\Domains\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function ($model): void {
            if (!empty($model->tenant_id)) {
                return;
            }
            $tenantId = app(TenantContext::class)->tenantId();
            if ($tenantId) {
                $model->tenant_id = $tenantId;
            }
        });

        static::addGlobalScope('tenant_scope', function (Builder $builder): void {
            $tenantId = app(TenantContext::class)->tenantId();
            if ($tenantId) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant_scope')
            ->where($this->getTable() . '.tenant_id', $tenantId);
    }
}

