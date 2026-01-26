<?php

namespace App\Domain\Tickets\Actions;

use App\Domain\Tickets\DTO\ReplyTicketData;
use App\Domain\Tickets\Events\TicketReplied;
use App\Domain\Tickets\Events\TicketStatusChanged;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Models\TicketAttachment;
use App\Domain\Tickets\Models\TicketMessage;
use App\Domain\Tickets\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReplyToTicketAction
{
    public function execute(ReplyTicketData $data): TicketMessage
    {
        return DB::transaction(function () use ($data) {
            $ticket = Ticket::query()->findOrFail($data->ticketId);
            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'customer_id' => $data->customerId,
                'sender_type' => $data->senderType,
                'sender_id' => $data->senderId,
                'body' => $data->body,
                'is_internal' => $data->isInternal,
            ]);

            foreach ($data->attachments as $file) {
                $path = $file->store('ticket-attachments', 'public');
                TicketAttachment::create([
                    'ticket_message_id' => $message->id,
                    'path' => $path,
                    'disk' => 'public',
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            $statusChanged = false;
            $fromStatus = $ticket->status;
            $toStatus = $ticket->status;

            if (!$data->isInternal) {
                if ($data->senderType === TicketMessage::SENDER_ADMIN) {
                    $toStatus = Ticket::STATUS_WAITING_CUSTOMER;
                }

                if ($data->senderType === TicketMessage::SENDER_CUSTOMER) {
                    $toStatus = Ticket::STATUS_WAITING_ADMIN;
                }
            }

            if ($toStatus !== $fromStatus) {
                $ticket->status = $toStatus;
                $statusChanged = true;
            }

            $ticket->last_activity_at = now();
            if ($toStatus !== Ticket::STATUS_CLOSED) {
                $ticket->closed_at = null;
            }
            $ticket->save();

            if ($statusChanged) {
                TicketStatusHistory::create([
                    'ticket_id' => $ticket->id,
                    'from_status' => $fromStatus,
                    'to_status' => $toStatus,
                    'changed_by_type' => $data->senderType,
                    'changed_by_id' => $data->senderId,
                ]);
            }

            DB::afterCommit(function () use ($ticket, $message, $statusChanged, $fromStatus, $toStatus, $data): void {
                $actor = $data->senderId ? User::find($data->senderId) : null;
                event(new TicketReplied($ticket, $message, $actor));

                if ($statusChanged) {
                    event(new TicketStatusChanged($ticket, $fromStatus, $toStatus, $actor, $data->senderType));
                }
            });

            return $message;
        });
    }
}
