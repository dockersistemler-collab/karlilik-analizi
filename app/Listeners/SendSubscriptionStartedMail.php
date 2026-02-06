<?php

namespace App\Listeners;

use App\Events\SubscriptionStarted;
use App\Models\MailLog;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Route;

class SendSubscriptionStartedMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(SubscriptionStarted $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }

        if ($this->isDeduped($event, $user->id)) {
            if (!$this->hasDedupedLog($event, $user->id)) {
                MailLog::create([
                    'key' => 'subscription.started',
                    'user_id' => $user->id,
                    'status' => 'deduped',
                    'provider_message_id' => null,
                    'error' => null,
                    'metadata_json' => [
                        'subscription_id' => $event->subscriptionId,
                        'plan_id' => $event->planId,
                        'occurred_at' => $event->occurredAt,
                        'reason' => 'dedupe_subscription_id',
                    ],
                    'sent_at' => null,
                ]);
            }
            return;
        }
$panelUrl = Route::has('portal.dashboard') ? route('portal.dashboard') : null;

        $this->sender->send('subscription.started', $user, [
            'user_name' => $user->name,
            'plan_name' => $event->planName,
            'started_at' => $event->startedAt,
            'ends_at' => $event->endsAt,
            'panel_url' => $panelUrl,
        ], [
            'subscription_id' => $event->subscriptionId,
            'plan_id' => $event->planId,
            'occurred_at' => $event->occurredAt,
        ]);
    }

    private function isDeduped(SubscriptionStarted $event, int $userId): bool
    {
        return MailLog::query()
            ->where('key', 'subscription.started')
            ->where('user_id', $userId)
            ->where('metadata_json->subscription_id', $event->subscriptionId)
            ->exists();
    }

    private function hasDedupedLog(SubscriptionStarted $event, int $userId): bool
    {
        return MailLog::query()
            ->where('key', 'subscription.started')
            ->where('user_id', $userId)
            ->where('status', 'deduped')
            ->where('metadata_json->subscription_id', $event->subscriptionId)
            ->exists();
    }
}




