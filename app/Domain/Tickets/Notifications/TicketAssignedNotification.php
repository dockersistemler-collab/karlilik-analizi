<?php

namespace App\Domain\Tickets\Notifications;

use App\Domain\Tickets\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public ?User $actor,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Yeni Ticket Ataması: '.$this->ticket->subject)
            ->line('Size yeni bir destek talebi atandı.')
            ->line('Atayan: '.($this->actor?->name ?? 'Sistem'))
            ->action('Talebi Görüntüle', route('super-admin.tickets.show', $this->ticket));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'actor' => $this->actor?->name, 'type' => 'ticket_assigned',
        ];
    }
}


