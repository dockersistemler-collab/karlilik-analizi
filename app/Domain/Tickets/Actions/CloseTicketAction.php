<?php

namespace App\Domain\Tickets\Actions;

use App\Domain\Tickets\Models\Ticket;
use App\Models\User;

class CloseTicketAction
{
    public function __construct(private ChangeTicketStatusAction $changeTicketStatus)
    {
    }

    public function execute(Ticket $ticket, ?User $actor, string $actorType): Ticket
    {
        return $this->changeTicketStatus->execute($ticket, Ticket::STATUS_CLOSED, $actor, $actorType);
    }
}
