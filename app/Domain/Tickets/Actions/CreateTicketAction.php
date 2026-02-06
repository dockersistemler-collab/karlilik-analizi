<?php

namespace App\Domain\Tickets\Actions;

use App\Domain\Tickets\DTO\CreateTicketData;
use App\Domain\Tickets\Events\TicketCreated;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Models\TicketAttachment;
use App\Domain\Tickets\Models\TicketMessage;
use App\Domain\Tickets\Models\TicketStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTicketAction
{
    public function execute(CreateTicketData $data): Ticket
    {
        $user = User::findOrFail($data->createdById);
        $subscription = $user->subscription;

        if (!$subscription || !$subscription->isActive()) {
            throw ValidationException::withMessages([
                'subscription' => 'Aktif abonelik olmadan destek talebi açamazsınız.',
            ]);
        }
$plan = $subscription->plan;
        $maxTickets = (int) ($plan?->max_tickets_per_month ?? 0);
        if ($maxTickets > 0) {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $count = Ticket::forCustomer($data->customerId)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            if ($count >= $maxTickets) {
                throw ValidationException::withMessages([
                    'limit' => 'Aylık destek talebi limitiniz doldu.',
                ]);
            }
        }

        if (in_array($data->priority, [Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT], true) && !$plan?->priority_support) {
            throw ValidationException::withMessages([
                'priority' => 'Bu öncelik seviyesi mevcut paketinizde desteklenmiyor.',
            ]);
        }

        return DB::transaction(function () use ($data, $user) {
            $ticket = Ticket::create([
                'customer_id' => $data->customerId,
                'created_by_id' => $data->createdById,
                'subject' => $data->subject,
                'status' => Ticket::STATUS_OPEN,
                'priority' => $data->priority,
                'channel' => $data->channel,
                'last_activity_at' => now(),
            ]);

            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'customer_id' => $data->customerId,
                'sender_type' => TicketMessage::SENDER_CUSTOMER,
                'sender_id' => $data->createdById,
                'body' => $data->body,
                'is_internal' => false,
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

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'from_status' => null,
                'to_status' => Ticket::STATUS_OPEN,
                'changed_by_type' => TicketMessage::SENDER_CUSTOMER,
                'changed_by_id' => $data->createdById,
            ]);

            DB::afterCommit(function () use ($ticket, $message, $user): void {
                event(new TicketCreated($ticket, $message, $user));
            });

            return $ticket;
        });
    }
}


