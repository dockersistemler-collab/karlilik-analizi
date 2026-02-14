<?php

namespace App\Domain\Tickets\Listeners;

use App\Domain\Tickets\Events\TicketStatusChanged;
use App\Domain\Tickets\Models\TicketMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WriteSystemMessage implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketStatusChanged $event): void
    {
        TicketMessage::create([
            'ticket_id' => $event->ticket->id,
            'customer_id' => $event->ticket->customer_id,
            'sender_type' => TicketMessage::SENDER_SYSTEM,
            'sender_id' => null,
            'body' => sprintf(
                'Durum değişti: %s -> %s',
                $this->label($event->fromStatus),
                $this->label($event->toStatus)
            ),
            'is_internal' => false,
        ]);
    }

    private function label(string $status): string
    {
        return match ($status) {
            'open' => 'Açık',
            'waiting_customer' => 'Müşteri yanıtı bekleniyor',
            'waiting_admin' => 'Destek yanıtı bekleniyor',
            'resolved' => 'Çözüldü',
            'closed' => 'Kapatıldı',
            default => $status,
        };
    }
}




