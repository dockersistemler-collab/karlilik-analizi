<?php

namespace App\Domains\Settlements\Repositories;

use App\Domains\Settlements\Models\Payout;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentPayoutRepository implements PayoutRepositoryInterface
{
    public function paginateForTenant(int $tenantId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['account_id'])) {
            $query->where('marketplace_account_id', (int) $filters['account_id']);
        }
        if (!empty($filters['from']) && !empty($filters['to'])) {
            $query->whereBetween('period_start', [$filters['from'], $filters['to']]);
        }

        return $query->latest('id')->paginate($perPage);
    }

    public function findForTenant(int $tenantId, int $payoutId): Payout
    {
        return Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($payoutId);
    }
}

