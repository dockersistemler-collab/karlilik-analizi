<?php

namespace App\Listeners;

use App\Events\TrialEnded;
use App\Models\MailLog;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Route;

class SendTrialEndedMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(TrialEnded $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }

        if ($this->isDeduped($user->id)) {
            if (!$this->hasDedupedLog($user->id)) {
                MailLog::create([
                    'key' => 'trial.ended',
                    'user_id' => $user->id,
                    'status' => 'deduped',
                    'provider_message_id' => null,
                    'error' => null,
                    'metadata_json' => [
                        'subscription_id' => $event->subscriptionId,
                        'plan_id' => $event->planId,
                        'occurred_at' => $event->occurredAt,
                        'reason' => 'dedupe_24h',
                    ],
                    'sent_at' => null,
                ]);
            }
            return;
        }
$pricingUrl = Route::has('pricing') ? route('pricing') : null;
        $dashboardUrl = Route::has('portal.dashboard') ? route('portal.dashboard') : null;

        $this->sender->send('trial.ended', $user, [
            'user_name' => $user->name,
            'trial_ended_at' => $event->trialEndedAt,
            'pricing_url' => $pricingUrl,
            'dashboard_url' => $dashboardUrl,
        ], [
            'subscription_id' => $event->subscriptionId,
            'plan_id' => $event->planId,
            'occurred_at' => $event->occurredAt,
        ]);
    }

    private function isDeduped(int $userId): bool
    {
        return MailLog::query()
            ->where('key', 'trial.ended')
            ->where('user_id', $userId)
            ->where('status', 'success')
            ->where('sent_at', '>=', now()->subDay())
            ->exists();
    }

    private function hasDedupedLog(int $userId): bool
    {
        return MailLog::query()
            ->where('key', 'trial.ended')
            ->where('user_id', $userId)
            ->where('status', 'deduped')
            ->where('created_at', '>=', now()->subDay())
            ->exists();
    }
}




