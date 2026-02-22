<?php

namespace App\Policies;

use App\Domains\Settlements\Models\Payout;
use App\Models\User;

class PayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payouts.view');
    }

    public function view(User $user, Payout $payout): bool
    {
        $tenantId = $user->tenant_id ?: ($user->isClient() ? $user->id : null);
        return $user->can('payouts.view') && (int) $payout->tenant_id === (int) $tenantId;
    }

    public function reconcile(User $user, Payout $payout): bool
    {
        $tenantId = $user->tenant_id ?: ($user->isClient() ? $user->id : null);
        return $user->can('payouts.reconcile') && (int) $payout->tenant_id === (int) $tenantId;
    }

    public function export(User $user, Payout $payout): bool
    {
        $tenantId = $user->tenant_id ?: ($user->isClient() ? $user->id : null);
        return $user->can('exports.create') && (int) $payout->tenant_id === (int) $tenantId;
    }
}

