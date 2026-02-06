<?php

namespace App\Listeners;

use App\Events\SubscriptionRenewed;
use App\Models\MailLog;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Route;

class SendSubscriptionRenewedMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(SubscriptionRenewed $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }

        if ($this->isDeduped($event, $user->id)) {
            if (!$this->hasDedupedLog($event, $user->id)) {
                MailLog::create([
                    'key' => 'subscription.renewed',
                    'user_id' => $user->id,
                    'status' => 'deduped',
                    'provider_message_id' => null,
                    'error' => null,
                    'metadata_json' => [
                        'subscription_id' => $event->subscriptionId,
                        'period_end' => $event->periodEnd,
                        'renewed_at' => $event->renewedAt,
                        'reason' => 'dedupe_period',
                    ],
                    'sent_at' => null,
                ]);
            }
            return;
        }
$panelUrl = Route::has('portal.dashboard') ? route('portal.dashboard') : null;

        $this->sender->send('subscription.renewed', $user, [
            'user_name' => $user->name,
            'plan_name' => $event->planName,
            'renewed_at' => $event->renewedAt,
            'period_start' => $event->periodStart,
            'period_end' => $event->periodEnd,
            'amount' => $event->amount,
            'currency' => $event->currency,
            'panel_url' => $panelUrl,
        ], [
            'subscription_id' => $event->subscriptionId,
            'period_end' => $event->periodEnd,
            'renewed_at' => $event->renewedAt,
        ]);
    }

    private function isDeduped(SubscriptionRenewed $event, int $userId): bool
    {
        return MailLog::query()
            ->where('key', 'subscription.renewed')
            ->where('user_id', $userId)
            ->where('metadata_json->subscription_id', $event->subscriptionId)
            ->where(function ($builder) use ($event): void {
                if ($event->periodEnd !== null) {
                    $builder->where('metadata_json->period_end', $event->periodEnd);
                    return;
                }
$builder->where('metadata_json->renewed_at', $event->renewedAt);
            })
            ->exists();
    }

    private function hasDedupedLog(SubscriptionRenewed $event, int $userId): bool
    {
        return MailLog::query()
            ->where('key', 'subscription.renewed')
            ->where('user_id', $userId)
            ->where('status', 'deduped')
            ->where('metadata_json->subscription_id', $event->subscriptionId)
            ->where(function ($builder) use ($event): void {
                if ($event->periodEnd !== null) {
                    $builder->where('metadata_json->period_end', $event->periodEnd);
                    return;
                }
$builder->where('metadata_json->renewed_at', $event->renewedAt);
            })
            ->exists();
    }
}




