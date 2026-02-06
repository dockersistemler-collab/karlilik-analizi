<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isClient() || $user->isSuperAdmin() || $user->role === 'support_agent';
    }

    public function view(User $user, Notification $notification): bool
    {
        return (int) $notification->tenant_id === (int) $user->id;
    }

    public function markRead(User $user, Notification $notification): bool
    {
        return $this->view($user, $notification);
    }
}