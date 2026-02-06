<?php

namespace App\Listeners\NotificationHub;

use App\Enums\NotificationSource;
use App\Enums\NotificationType;
use App\Events\WebhookSignatureInvalid;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWebhookSignatureInvalidNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(WebhookSignatureInvalid $event): void
    {
        $user = User::query()->find($event->tenantId);
        if (!$user) {
            return;
        }
$title = 'Webhook imzası doğrulanamadı';
        $body = $event->reason;

        $this->notifications->notifyUser($user, [
            'tenant_id' => $event->tenantId,
            'user_id' => $user->id,
            'marketplace' => $event->source,
            'source' => NotificationSource::Webhook->value,
            'type' => NotificationType::Critical->value,
            'title' => $title,
            'body' => $body,
            'action_url' => route('portal.webhooks.index'),
            'dedupe_key' => 'webhook_signature_invalid:'.($event->source ?? 'unknown'),
        ]);
    }
}

