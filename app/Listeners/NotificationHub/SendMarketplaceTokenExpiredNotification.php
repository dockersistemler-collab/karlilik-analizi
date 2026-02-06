<?php

namespace App\Listeners\NotificationHub;

use App\Enums\NotificationSource;
use App\Enums\NotificationType;
use App\Events\MarketplaceTokenExpired;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMarketplaceTokenExpiredNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(MarketplaceTokenExpired $event): void
    {
        $user = User::query()->find($event->tenantId);
        if (!$user) {
            return;
        }
$title = 'Pazaryeri bağlantısı süresi doldu';
        $body = "{$event->marketplace} bağlantısı yenilenmeli. {$event->reason}";

        $actionUrl = route('portal.integrations.edit', ['marketplace' => $event->marketplace]);

        $this->notifications->notifyUser($user, [
            'tenant_id' => $event->tenantId,
            'user_id' => $user->id,
            'marketplace' => $event->marketplace,
            'source' => NotificationSource::System->value,
            'type' => NotificationType::Critical->value,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'dedupe_key' => 'marketplace_token_expired:'.$event->marketplace,
        ]);
    }
}

