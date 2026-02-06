<?php

namespace App\Listeners\NotificationHub;

use App\Enums\NotificationSource;
use App\Enums\NotificationType;
use App\Events\InvoiceCreationFailed;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvoiceCreationFailedNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(InvoiceCreationFailed $event): void
    {
        $user = User::query()->find($event->tenantId);
        if (!$user) {
            return;
        }
$title = 'Fatura oluşturma başarısız';
        $body = $event->reason;
        if ($event->invoiceId) {
            $body = "Fatura #{$event->invoiceId}: {$event->reason}";
        }
$this->notifications->notifyUser($user, [
            'tenant_id' => $event->tenantId,
            'user_id' => $user->id,
            'marketplace' => $event->marketplace,
            'source' => NotificationSource::Invoice->value,
            'type' => NotificationType::Critical->value,
            'title' => $title,
            'body' => $body,
            'data' => ['invoice_id' => $event->invoiceId],
            'action_url' => $event->invoiceId ? route('portal.invoices.show', $event->invoiceId) : null,
            'dedupe_key' => 'invoice_failed:'.($event->invoiceId ?? 'na'),
        ]);
    }
}

