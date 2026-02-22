<?php

namespace App\Policies;

use App\Models\MarketplaceAccount;
use App\Models\User;

class MarketplaceAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('marketplace_accounts.manage');
    }

    public function view(User $user, MarketplaceAccount $account): bool
    {
        $tenantId = $user->tenant_id ?: ($user->isClient() ? $user->id : null);
        return $user->can('marketplace_accounts.manage') && (int) $account->tenant_id === (int) $tenantId;
    }

    public function create(User $user): bool
    {
        return $user->can('marketplace_accounts.manage');
    }

    public function update(User $user, MarketplaceAccount $account): bool
    {
        $tenantId = $user->tenant_id ?: ($user->isClient() ? $user->id : null);
        return $user->can('marketplace_accounts.manage') && (int) $account->tenant_id === (int) $tenantId;
    }

    public function delete(User $user, MarketplaceAccount $account): bool
    {
        return $this->update($user, $account);
    }
}

