<?php

namespace App\Domain\Tickets\Actions;

use App\Domain\Tickets\Events\TicketAssigned;
use App\Domain\Tickets\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignTicketAction
{
    public function execute(Ticket $ticket, int $assigneeId, User $actor): Ticket
    {
        $assignee = User::findOrFail($assigneeId);

        if (!$assignee->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'assigned_to_id' => 'Seçilen kullanıcı destek yöneticisi değil.',
            ]);
        }
$ticket->assigned_to_id = $assignee->id;
        $ticket->save();

        DB::afterCommit(function () use ($ticket, $assignee, $actor): void {
            event(new TicketAssigned($ticket, $assignee, $actor));
        });

        return $ticket;
    }
}


