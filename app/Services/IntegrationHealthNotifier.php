<?php

namespace App\Services;

use App\Enums\NotificationSource;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Services\IncidentService;
use App\Services\Features\FeatureGate;

class IntegrationHealthNotifier
{
    public function __construct(
        private readonly IntegrationHealthService $health,
        private readonly NotificationService $notifications,
        private readonly IncidentService $incidents,
        private readonly FeatureGate $features
    ) {
    }

    public function notifyTenant(int $tenantId): void
    {
        $user = User::query()->find($tenantId);
        if (!$user) {
            return;
        }

        if (!$this->features->enabled('health_notifications', $user)) {
            return;
        }
$summary = $this->health->getTenantHealthSummary($tenantId);
        $windowHours = (int) config('integration_health.window_hours', 24);

        foreach ($summary as $marketplace) {
            $status = (string) ($marketplace['status'] ?? 'OK');
            $code = (string) ($marketplace['marketplace_code'] ?? '');
            if ($code === '') {
                continue;
            }

            if (in_array($status, ['DOWN', 'DEGRADED'], true)) {
                $this->notifyStatus($user, $marketplace, $windowHours);
                continue;
            }

            if ($status === 'OK') {
                $this->notifyRecoveryIfNeeded($user, $marketplace, $windowHours);
            }
        }
    }

    /**
     * @param array<string,mixed> $marketplace
     */
    private function notifyStatus(User $user, array $marketplace, int $windowHours): void
    {
        $status = (string) $marketplace['status'];
        $code = (string) $marketplace['marketplace_code'];
        $name = (string) $marketplace['marketplace_name'];
        $errorCount = (int) ($marketplace['error_count_24h'] ?? 0);
        $lastSuccess = $this->formatTime($marketplace['last_success_at'] ?? null);
        $lastError = $this->formatError($marketplace['last_error'] ?? null);

        $title = $status === 'DOWN'
            ? "{$name} baglantisi sorunlu (DOWN)"
            : "{$name} entegrasyonunda yavaslama (DEGRADED)";

        $body = "Son {$windowHours} saatte {$errorCount} hata. Son basarili: {$lastSuccess}. Son hata: {$lastError}";

        $dedupeMinutes = $status === 'DOWN' ? 60 : 360;

        $incidentKey = "health:{$user->id}:{$code}:" . strtolower($status);
        $incident = null;
        if ($this->features->enabled('incidents', $user)) {
            $incident = $this->incidents->openOrTouch($user->id, $incidentKey, [
                'marketplace' => $code,
                'title' => $title,
                'severity' => $status === 'DOWN' ? 'critical' : 'operational',
                'meta' => [
                    'status' => $status,
                    'error_count_24h' => $errorCount,
                    'last_success_at' => $marketplace['last_success_at']?->toIso8601String(), 'last_error' => $lastError,
                ],
            ]);

            $this->incidents->addEvent($incident, 'health_status', $body, [
                'status' => $status,
                'marketplace' => $code,
            ]);
        }
$this->notifications->notifyUser($user, [
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'marketplace' => $code,
            'source' => NotificationSource::System->value,
            'type' => $status === 'DOWN' ? NotificationType::Critical->value : NotificationType::Operational->value,
            'title' => $title,
            'body' => $body,
            'action_url' => route('portal.integrations.health', ['marketplace' => $code]),
            'dedupe_key' => "health:{$user->id}:{$code}:" . strtolower($status),
            'group_key' => "health:{$user->id}:{$code}",
            'dedupe_window_minutes' => $dedupeMinutes,
            'data' => [
                'status' => $status,
                'incident_id' => $incident?->id, 'metrics' => [
                    'error_count_24h' => $errorCount,
                    'last_success_at' => $marketplace['last_success_at']?->toIso8601String(), 'last_attempt_at' => $marketplace['last_attempt_at']?->toIso8601String(),
                ],
                'marketplace' => $code,
            ],
        ]);
    }

    /**
     * @param array<string,mixed> $marketplace
     */
    private function notifyRecoveryIfNeeded(User $user, array $marketplace, int $windowHours): void
    {
        $code = (string) $marketplace['marketplace_code'];
        $name = (string) $marketplace['marketplace_name'];

        $previous = $this->lastHealthStatus($user->id, $code);
        if (!in_array($previous, ['DOWN', 'DEGRADED'], true)) {
            return;
        }
$resolvedIncident = null;
        if ($this->features->enabled('incidents', $user)) {
            $resolvedDown = $this->incidents->autoResolveByKey($user->id, "health:{$user->id}:{$code}:down", [
                'resolved_by' => 'health_recovery',
            ]);
            $resolvedDegraded = $this->incidents->autoResolveByKey($user->id, "health:{$user->id}:{$code}:degraded", [
                'resolved_by' => 'health_recovery',
            ]);
            $resolvedIncident = $resolvedDown ?? $resolvedDegraded;
        }
$title = "{$name} entegrasyonu duzeldi (OK)";
        $body = "Son {$windowHours} saatte hata gorunmedi. Entegrasyon tekrar stabil.";

        $this->notifications->notifyUser($user, [
            'tenant_id' => $user->id,
            'user_id' => $user->id,
            'marketplace' => $code,
            'source' => NotificationSource::System->value,
            'type' => NotificationType::Operational->value,
            'title' => $title,
            'body' => $body,
            'action_url' => route('portal.integrations.health', ['marketplace' => $code]),
            'dedupe_key' => "health:{$user->id}:{$code}:recovered:" . strtolower((string) $previous),
            'group_key' => "health:{$user->id}:{$code}",
            'dedupe_window_minutes' => 360,
            'data' => [
                'status' => 'OK',
                'previous_status' => $previous,
                'incident_id' => $resolvedIncident?->id, 'marketplace' => $code,
            ],
        ]);
    }

    private function lastHealthStatus(int $tenantId, string $marketplace): ?string
    {
        $notification = Notification::query()
            ->where('tenant_id', $tenantId)
            ->where('group_key', "health:{$tenantId}:{$marketplace}")
            ->latest('created_at')
            ->first();

        if (!$notification) {
            return null;
        }
$data = is_array($notification->data) ? $notification->data : [];
        $status = $data['status'] ?? null;

        return is_string($status) ? strtoupper($status) : null;
    }

    private function formatTime(?Carbon $value): string
    {
        if (!$value) {
            return 'Yok';
        }

        return $value->format('d.m.Y H:i');
    }

    /**
     * @param array<string,mixed>|null $lastError
     */
    private function formatError(?array $lastError): string
    {
        if (!$lastError) {
            return 'Yok';
        }
$message = trim((string) ($lastError['message'] ?? ''));
        if ($message === '') {
            return 'Yok';
        }

        return Str::limit($message, 140);
    }
}


