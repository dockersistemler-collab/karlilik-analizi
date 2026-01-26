<?php

namespace App\Domain\Tickets\Actions;

use App\Domain\Tickets\Events\TicketStatusChanged;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangeTicketStatusAction
{
    public function execute(Ticket $ticket, string $toStatus, ?User $actor, string $actorType): Ticket
    {
        $fromStatus = $ticket->status;

        if ($fromStatus === $toStatus) {
            return $ticket;
        }

        $ticket->status = $toStatus;
        $ticket->last_activity_at = now();
        $ticket->closed_at = $toStatus === Ticket::STATUS_CLOSED ? now() : null;
        $ticket->save();

        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_type' => $actorType,
            'changed_by_id' => $actor?->id,
        ]);

        DB::afterCommit(function () use ($ticket, $fromStatus, $toStatus, $actor, $actorType): void {
            event(new TicketStatusChanged($ticket, $fromStatus, $toStatus, $actor, $actorType));
        });

        return $ticket;
    }
}
