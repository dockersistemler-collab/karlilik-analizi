<?php

namespace App\Listeners;

use App\Events\MarketplaceConnectionLost;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMarketplaceConnectionLostMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(MarketplaceConnectionLost $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }
$this->sender->send('mp.connection_lost', $user, [
            'user_name' => $user->name,
            'marketplace' => $event->marketplace,
            'store_id' => $event->storeId,
            'reason' => $event->reason,
            'occurred_at' => $event->occurredAt,
        ], [
            'marketplace' => $event->marketplace,
            'store_id' => $event->storeId,
            'reason' => $event->reason,
            'occurred_at' => $event->occurredAt,
        ]);
    }
}
