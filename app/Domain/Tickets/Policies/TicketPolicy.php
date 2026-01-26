<?php

namespace App\Domain\Tickets\Policies;

use App\Domain\Tickets\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isClient();
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isClient() && $ticket->customer_id === $user->id;
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return false;
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return false;
    }
}
