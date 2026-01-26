<?php

namespace App\Domain\Tickets\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait CustomerScoped
{
    protected static function bootCustomerScoped(): void
    {
        static::addGlobalScope('customer', function (Builder $builder): void {
            $user = auth()->user();

            if (!$user) {
                return;
            }

            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return;
            }

            if (method_exists($user, 'isClient') && $user->isClient()) {
                $builder->where($builder->getModel()->getTable().'.customer_id', $user->id);
            }
        });
    }

    public function scopeForCustomer(Builder $builder, int $customerId): Builder
    {
        return $builder->withoutGlobalScope('customer')
            ->where($builder->getModel()->getTable().'.customer_id', $customerId);
    }
}
