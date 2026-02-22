<?php

namespace App\Policies;

use App\Domains\Settlements\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('disputes.view');
    }

    public function view(User $user, Dispute $dispute): bool
    {
        $tenantId = $user->tenant_id ?: ($user->isClient() ? $user->id : null);
        return $user->can('disputes.view') && (int) $dispute->tenant_id === (int) $tenantId;
    }

    public function update(User $user, Dispute $dispute): bool
    {
        $tenantId = $user->tenant_id ?: ($user->isClient() ? $user->id : null);
        return $user->can('disputes.manage') && (int) $dispute->tenant_id === (int) $tenantId;
    }
}

