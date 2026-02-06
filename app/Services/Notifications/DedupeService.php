<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use Illuminate\Support\Carbon;

class DedupeService
{
    public function findRecent(int $tenantId, string $dedupeKey, string $channel, int $minutes = 10): ?Notification
    {
        return Notification::query()
            ->where('tenant_id', $tenantId)
            ->where('channel', $channel)
            ->where('dedupe_key', $dedupeKey)
            ->where('created_at', '>=', Carbon::now()->subMinutes($minutes))
            ->latest('created_at')
            ->first();
    }
}