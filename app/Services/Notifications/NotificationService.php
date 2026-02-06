<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Jobs\SendNotificationEmailJob;
use App\Models\Notification;
use App\Models\NotificationAuditLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class NotificationService
{
    public function __construct(
        private readonly DedupeService $dedupe,
        private readonly PreferenceResolver $preferences,
        private readonly EmailSuppressionService $suppressions
    ) {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function notifyUser(User $user, array $payload): void
    {
        $type = NotificationType::from((string) ($payload['type'] ?? NotificationType::Info->value));
        $marketplace = $payload['marketplace'] ?? null;

        if ($this->preferences->isEnabled($user, $type, NotificationChannel::InApp, $marketplace)) {
            $this->createNotification(array_merge($payload, [
                'channel' => NotificationChannel::InApp->value,
            ]));
        }

        if ($this->preferences->isEnabled($user, $type, NotificationChannel::Email, $marketplace)) {
            $notification = $this->createNotification(array_merge($payload, [
                'channel' => NotificationChannel::Email->value,
            ]));

            if ($notification) {
                $recipient = $user->notification_email ?: $user->email;
                if ($recipient) {
                    $suppression = $this->suppressions->findSuppression($notification->tenant_id, $recipient);
                    if ($suppression) {
                        $this->logEmailAudit($notification, 'email_suppressed', [
                            'reason' => $suppression->reason,
                            'suppression_id' => $suppression->id,
                            'source' => $suppression->source,
                        ]);
                        return;
                    }

                    if ($this->isConfigSuppressed($recipient)) {
                        $this->logEmailAudit($notification, 'email_suppressed', [
                            'reason' => 'config',
                            'suppression_id' => null,
                            'source' => 'config',
                        ]);
                        return;
                    }
                }
                $quiet = $this->resolveQuietHours($user, $type, $marketplace);
                if ($quiet) {
                    $releaseAt = $this->nextQuietHoursReleaseAt($quiet, Carbon::now());
                    if ($releaseAt) {
                        $delaySeconds = $releaseAt->diffInSeconds(Carbon::now()) ?: 60;
                        SendNotificationEmailJob::dispatch($notification->id)->delay($releaseAt);
                        $this->logEmailAudit($notification, 'email_deferred', [
                            'delay_seconds' => $delaySeconds,
                            'release_at' => $releaseAt->toIso8601String(),
                        ]);
                        return;
                    }
                }

                SendNotificationEmailJob::dispatch($notification->id);
            }
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function createNotification(array $payload): ?Notification
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            return null;
        }
        $channel = (string) ($payload['channel'] ?? NotificationChannel::InApp->value);
        $dedupeKey = isset($payload['dedupe_key']) ? trim((string) $payload['dedupe_key']) : null;
        $dedupeWindow = isset($payload['dedupe_window_minutes'])
            ? max(1, (int) $payload['dedupe_window_minutes'])
            : null;
        if ($dedupeKey !== null && $dedupeKey !== '') {
            $existing = $this->dedupe->findRecent($tenantId, $dedupeKey, $channel, $dedupeWindow ?? 10);
            if ($existing) {
                $existing->touch();
                return $existing;
            }
        }

        return Notification::create([
            'tenant_id' => $tenantId,
            'user_id' => $payload['user_id'] ?? null,
            'audience_role' => $payload['audience_role'] ?? null,
            'marketplace' => $payload['marketplace'] ?? null,
            'source' => $payload['source'] ?? null,
            'type' => $payload['type'] ?? NotificationType::Info->value,
            'channel' => $channel,
            'title' => (string) ($payload['title'] ?? 'Bildirim'),
            'body' => (string) ($payload['body'] ?? ''),
            'data' => $payload['data'] ?? null,
            'action_url' => $payload['action_url'] ?? null,
            'dedupe_key' => $dedupeKey,
            'group_key' => $payload['group_key'] ?? null,
            'read_at' => $payload['read_at'] ?? null,
        ]);
    }

    public function resolveQuietHours(User $user, NotificationType $type, ?string $marketplace): ?array
    {
        return $this->preferences->resolveQuietHours($user, $type, $marketplace);
    }

    public function nextQuietHoursReleaseAt(array $quietHours, Carbon $now): ?Carbon
    {
        $start = $quietHours['start'] ?? null;
        $end = $quietHours['end'] ?? null;
        $tz = $quietHours['tz'] ?? 'Europe/Istanbul';

        if (!$start || !$end) {
            return null;
        }
$localNow = $now->copy()->timezone($tz);
        $startAt = Carbon::createFromFormat('H:i', $start, $tz)
            ->setDate($localNow->year, $localNow->month, $localNow->day);
        $endAt = Carbon::createFromFormat('H:i', $end, $tz)
            ->setDate($localNow->year, $localNow->month, $localNow->day);

        $crossesMidnight = $startAt->greaterThanOrEqualTo($endAt);

        if ($crossesMidnight && $localNow->lessThan($endAt)) {
            $startAt = $startAt->subDay();
        }
$isQuiet = $crossesMidnight
            ? ($localNow->greaterThanOrEqualTo($startAt) || $localNow->lessThan($endAt))
            : ($localNow->greaterThanOrEqualTo($startAt) && $localNow->lessThan($endAt));

        if (!$isQuiet) {
            return null;
        }

        if ($crossesMidnight && $localNow->greaterThanOrEqualTo($startAt)) {
            $endAt = $endAt->addDay();
        }

        return $endAt->timezone($now->timezone);
    }

    private function isConfigSuppressed(string $recipient): bool
    {
        $suppressed = config('mail.suppressed', []);
        if (!is_array($suppressed) || $suppressed === []) {
            return false;
        }
$recipient = strtolower(trim($recipient));
        if ($recipient === '') {
            return false;
        }
$normalized = array_map(static fn ($value) => strtolower(trim((string) $value)), $suppressed);
        return in_array($recipient, $normalized, true);
    }

    /**
     * @param array<string,mixed> $meta
     */
    public function logEmailAudit(Notification $notification, string $action, array $meta = []): void
    {
        NotificationAuditLog::create([
            'tenant_id' => $notification->tenant_id,
            'actor_user_id' => null,
            'target_user_id' => $notification->user_id ?: $notification->tenant_id,
            'action' => $action,
            'reason' => null,
            'ip' => null,
            'user_agent' => null,
            'meta' => $meta,
        ]);
    }
}
