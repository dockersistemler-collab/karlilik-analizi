<?php

namespace App\Listeners;

use App\Events\QuotaWarningReached;
use App\Models\MailLog;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class SendQuotaWarningMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(QuotaWarningReached $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }
$periodKey = $event->period ?? 'na';

        if ($this->isDeduped($event, $user->id, $periodKey)) {
            if (!$this->hasRecentDedupedLog($event, $user->id)) {
                MailLog::create([
                    'key' => 'quota.warning_80',
                    'user_id' => $user->id,
                    'status' => 'deduped',
                    'provider_message_id' => null,
                    'error' => null,
                    'metadata_json' => [
                        'quota_type' => $event->quotaType,
                        'period' => $periodKey,
                        'used' => $event->used,
                        'limit' => $event->limit,
                        'occurred_at' => $event->occurredAt,
                        'reason' => 'dedupe_7d',
                    ],
                    'sent_at' => null,
                ]);
            }
            return;
        }
$quotaLabel = match ($event->quotaType) {
            'products' => 'Ürün',
            'marketplaces' => 'Mağaza',
            'orders_per_month' => 'Aylık Sipariş',
            default => $event->quotaType,
        };

        $pricingUrl = Route::has('pricing') ? route('pricing') : null;
        $dashboardUrl = Route::has('portal.dashboard') ? route('portal.dashboard') : null;

        $this->sender->send('quota.warning_80', $user, [
            'user_name' => $user->name,
            'quota_type_label' => $quotaLabel,
            'used' => $event->used,
            'limit' => $event->limit,
            'percent' => $event->percent,
            'pricing_url' => $pricingUrl,
            'dashboard_url' => $dashboardUrl,
        ], [
            'quota_type' => $event->quotaType,
            'period' => $periodKey,
            'used' => $event->used,
            'limit' => $event->limit,
        ]);
    }

    private function isDeduped(QuotaWarningReached $event, int $userId, string $periodKey): bool
    {
        $since = Carbon::now()->subDays(7);

        $logs = MailLog::query()
            ->where('key', 'quota.warning_80')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->get();

        return $logs->contains(function ($log) use ($event, $periodKey): bool {
            $meta = is_array($log->metadata_json) ? $log->metadata_json : [];
            return $log->status === 'success'
                && ($meta['quota_type'] ?? null) === $event->quotaType
                && ($meta['period'] ?? null) === $periodKey;
        });
    }

    private function hasRecentDedupedLog(QuotaWarningReached $event, int $userId): bool
    {
        $since = Carbon::now()->subDays(7);
        $periodKey = $event->period ?? 'na';

        $logs = MailLog::query()
            ->where('key', 'quota.warning_80')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->get();

        return $logs->contains(function ($log) use ($event, $periodKey): bool {
            $meta = is_array($log->metadata_json) ? $log->metadata_json : [];
            return $log->status === 'deduped'
                && ($meta['quota_type'] ?? null) === $event->quotaType
                && ($meta['period'] ?? null) === $periodKey;
        });
    }
}




