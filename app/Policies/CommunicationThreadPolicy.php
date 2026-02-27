<?php

namespace App\Policies;

use App\Models\CommunicationThread;
use App\Models\User;

class CommunicationThreadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isClient();
    }

    public function view(User $user, CommunicationThread $thread): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) ($thread->marketplaceStore?->user_id ?? 0) === (int) $user->id;
    }

    public function reply(User $user, CommunicationThread $thread): bool
    {
        return $this->view($user, $thread);
    }

    public function manageSettings(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}

