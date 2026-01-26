<?php

namespace App\Domain\Tickets\Listeners;

use App\Domain\Tickets\Events\TicketAssigned;
use App\Domain\Tickets\Events\TicketCreated;
use App\Domain\Tickets\Events\TicketReplied;
use App\Domain\Tickets\Notifications\TicketAssignedNotification;
use App\Domain\Tickets\Notifications\TicketCreatedNotification;
use App\Domain\Tickets\Notifications\TicketRepliedNotification;
use App\Domain\Tickets\Models\TicketMessage;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTicketNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketCreated|TicketReplied|TicketAssigned $event): void
    {
        if ($event instanceof TicketCreated) {
            $this->notifyAdmins(
                $event->ticket,
                new TicketCreatedNotification($event->ticket, $event->message)
            );
        }

        if ($event instanceof TicketReplied) {
            if ($event->message->sender_type === TicketMessage::SENDER_ADMIN) {
                $event->ticket->customer?->notify(new TicketRepliedNotification($event->ticket, $event->message));
            } else {
                $this->notifyAdmins(
                    $event->ticket,
                    new TicketRepliedNotification($event->ticket, $event->message),
                    $event->actor
                );
            }
        }

        if ($event instanceof TicketAssigned) {
            if ($event->assignee->id !== $event->actor?->id) {
                $event->assignee->notify(new TicketAssignedNotification($event->ticket, $event->actor));
            }
        }
    }

    private function notifyAdmins($ticket, $notification, ?User $exclude = null): void
    {
        $admins = User::query()
            ->where('role', 'super_admin')
            ->when($exclude, function ($query) use ($exclude) {
                $query->where('id', '!=', $exclude->id);
            })
            ->get();

        $admins->each(function (User $admin) use ($notification): void {
            $admin->notify($notification);
        });
    }
}
