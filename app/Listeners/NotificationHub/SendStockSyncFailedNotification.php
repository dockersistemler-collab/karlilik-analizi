<?php

namespace App\Listeners\NotificationHub;

use App\Enums\NotificationSource;
use App\Enums\NotificationType;
use App\Events\StockSyncFailed;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendStockSyncFailedNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(StockSyncFailed $event): void
    {
        $user = User::query()->find($event->tenantId);
        if (!$user) {
            return;
        }
$title = 'Stok senkronizasyonu başarısız';
        $body = $event->reason;
        if ($event->sku) {
            $body = "SKU {$event->sku}: {$event->reason}";
        }
$this->notifications->notifyUser($user, [
            'tenant_id' => $event->tenantId,
            'user_id' => $user->id,
            'marketplace' => $event->marketplace,
            'source' => NotificationSource::StockSync->value,
            'type' => NotificationType::Operational->value,
            'title' => $title,
            'body' => $body,
            'data' => ['sku' => $event->sku],
            'dedupe_key' => 'stock_sync_failed:'.($event->marketplace ?? 'unknown').':'.($event->sku ?? 'na'),
        ]);
    }
}