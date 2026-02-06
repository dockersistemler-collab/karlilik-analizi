<?php

namespace App\Listeners;

use App\Events\QuotaExceeded;
use App\Models\MailLog;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class SendQuotaExceededMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(QuotaExceeded $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }

        if ($this->isDeduped($event, $user->id)) {
            if (!$this->hasRecentDedupedLog($event, $user->id)) {
                MailLog::create([
                    'key' => 'quota.exceeded',
                    'user_id' => $user->id,
                    'status' => 'deduped',
                    'provider_message_id' => null,
                    'error' => null,
                    'metadata_json' => [
                        'quota_key' => $event->quotaKey,
                        'period' => $event->period,
                        'occurred_at' => $event->occurredAt,
                        'reason' => 'dedupe_quota_24h',
                    ],
                    'sent_at' => null,
                ]);
            }
            return;
        }
$upgradeUrl = Route::has('portal.modules.mine') ? route('portal.modules.mine') : null;
        $pricingUrl = Route::has('pricing') ? route('pricing') : null;

        $this->sender->send('quota.exceeded', $user, [
            'user_name' => $user->name,
            'quota_key' => $event->quotaKey,
            'used' => $event->used,
            'limit' => $event->limit,
            'period' => $event->period,
            'reset_at' => $event->resetAt,
            'upgrade_url' => $upgradeUrl,
            'pricing_url' => $pricingUrl,
        ], [
            'quota_key' => $event->quotaKey,
            'period' => $event->period,
            'occurred_at' => $event->occurredAt,
        ]);
    }

    private function isDeduped(QuotaExceeded $event, int $userId): bool
    {
        $since = Carbon::now()->subHours(24);

        return MailLog::query()
            ->where('key', 'quota.exceeded')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->where('metadata_json->quota_key', $event->quotaKey)
            ->where('metadata_json->period', $event->period)
            ->exists();
    }

    private function hasRecentDedupedLog(QuotaExceeded $event, int $userId): bool
    {
        $since = Carbon::now()->subHours(24);

        return MailLog::query()
            ->where('key', 'quota.exceeded')
            ->where('user_id', $userId)
            ->where('status', 'deduped')
            ->where('created_at', '>=', $since)
            ->where('metadata_json->quota_key', $event->quotaKey)
            ->where('metadata_json->period', $event->period)
            ->exists();
    }
}




