<?php

namespace App\Domain\Tickets\Notifications;

use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketRepliedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public TicketMessage $message,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $route = $notifiable->isSuperAdmin()
            ? route('super-admin.tickets.show', $this->ticket)
            : route('portal.tickets.show', $this->ticket);

        return (new MailMessage())
            ->subject('Destek Talebine Yanıt: '.$this->ticket->subject)
            ->line('Talebinize yeni bir yanıt geldi.')
            ->action('Talebi Görüntüle', $route);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'message' => $this->message->body,
            'type' => 'ticket_replied',
        ];
    }
}



