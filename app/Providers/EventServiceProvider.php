<?php

namespace App\Providers;

use App\Domain\Tickets\Events\TicketAssigned;
use App\Domain\Tickets\Events\TicketCreated;
use App\Domain\Tickets\Events\TicketReplied;
use App\Domain\Tickets\Events\TicketStatusChanged;
use App\Domain\Tickets\Listeners\SendTicketNotification;
use App\Domain\Tickets\Listeners\WriteSystemMessage;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TicketCreated::class => [
            SendTicketNotification::class,
        ],
        TicketReplied::class => [
            SendTicketNotification::class,
        ],
        TicketAssigned::class => [
            SendTicketNotification::class,
        ],
        TicketStatusChanged::class => [
            WriteSystemMessage::class,
        ],
    ];
}
