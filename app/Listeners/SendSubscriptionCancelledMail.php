<?php

namespace App\Listeners;

use App\Events\SubscriptionCancelled;
use App\Models\MailLog;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Route;

class SendSubscriptionCancelledMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(SubscriptionCancelled $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }

        if ($this->isDeduped($event, $user->id)) {
            if (!$this->hasDedupedLog($event, $user->id)) {
                MailLog::create([
                    'key' => 'subscription.cancelled',
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
$reactivateUrl = Route::has('pricing') ? route('pricing') : null;
        $plansUrl = Route::has('pricing') ? route('pricing') : null;

        $this->sender->send('subscription.cancelled', $user, [
            'user_name' => $user->name,
            'plan_name' => $event->planName,
            'cancelled_at' => $event->cancelledAt,
            'access_ends_at' => $event->accessEndsAt,
            'reason' => $event->reason,
            'reactivate_url' => $reactivateUrl,
            'plans_url' => $plansUrl,
        ], [
            'subscription_id' => $event->subscriptionId,
            'plan_id' => $event->planId,
            'reason' => $event->reason,
            'occurred_at' => $event->occurredAt,
        ]);
    }

    private function isDeduped(SubscriptionCancelled $event, int $userId): bool
    {
        return MailLog::query()
            ->where('key', 'subscription.cancelled')
            ->where('user_id', $userId)
            ->where('metadata_json->subscription_id', $event->subscriptionId)
            ->exists();
    }

    private function hasDedupedLog(SubscriptionCancelled $event, int $userId): bool
    {
        return MailLog::query()
            ->where('key', 'subscription.cancelled')
            ->where('user_id', $userId)
            ->where('status', 'deduped')
            ->where('metadata_json->subscription_id', $event->subscriptionId)
            ->exists();
    }
}
