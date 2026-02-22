<?php

namespace App\Domains\Settlements\Repositories;

use App\Domains\Settlements\Models\Payout;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PayoutRepositoryInterface
{
    public function paginateForTenant(int $tenantId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function findForTenant(int $tenantId, int $payoutId): Payout;
}

