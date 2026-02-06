<?php

namespace App\Listeners\NotificationHub;

use App\Enums\NotificationSource;
use App\Enums\NotificationType;
use App\Events\OrderSyncFailed;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderSyncFailedNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(OrderSyncFailed $event): void
    {
        $user = User::query()->find($event->tenantId);
        if (!$user) {
            return;
        }
$title = 'Sipariş senkronizasyonu başarısız';
        $body = $event->reason;
        if ($event->orderId) {
            $body = "Sipariş #{$event->orderId}: {$event->reason}";
        }
$this->notifications->notifyUser($user, [
            'tenant_id' => $event->tenantId,
            'user_id' => $user->id,
            'marketplace' => $event->marketplace,
            'source' => NotificationSource::OrderSync->value,
            'type' => NotificationType::Operational->value,
            'title' => $title,
            'body' => $body,
            'data' => ['order_id' => $event->orderId],
            'action_url' => $event->orderId ? route('portal.orders.show', $event->orderId) : null,
            'dedupe_key' => 'order_sync_failed:'.($event->marketplace ?? 'unknown').':'.($event->orderId ?? 'na'),
        ]);
    }
}

