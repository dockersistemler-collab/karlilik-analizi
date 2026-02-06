<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentEvent;
use App\Models\NotificationAuditLog;
use Illuminate\Support\Carbon;

class IncidentService
{
    /**
     * @param array<string,mixed> $attrs
     */
    public function openOrTouch(int $tenantId, string $key, array $attrs): Incident
    {
        $now = Carbon::now();
        $incident = Incident::query()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        $title = (string) ($attrs['title'] ?? 'Incident');
        $severity = (string) ($attrs['severity'] ?? 'operational');

        if (!$incident) {
            return Incident::create([
                'tenant_id' => $tenantId,
                'marketplace' => $attrs['marketplace'] ?? null,
                'key' => $key,
                'title' => $title,
                'status' => 'open',
                'severity' => $severity,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'resolved_at' => null,
                'meta' => $attrs['meta'] ?? null,
            ]);
        }
$incident->title = $title;
        $incident->severity = $this->maxSeverity($incident->severity, $severity);
        $incident->last_seen_at = $now;
        $incident->meta = $attrs['meta'] ?? $incident->meta;
        if ($incident->status === 'resolved') {
            $incident->status = 'open';
            $incident->resolved_at = null;
        }
$incident->save();

        return $incident;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function addEvent(Incident $incident, string $type, string $message, array $data = []): IncidentEvent
    {
        return IncidentEvent::create([
            'incident_id' => $incident->id,
            'tenant_id' => $incident->tenant_id,
            'type' => $type,
            'message' => $message,
            'data' => $data ?: null,
            'created_at' => Carbon::now(),
        ]);
    }

    public function acknowledge(Incident $incident, int $actorUserId): void
    {
        if ($incident->status === 'acknowledged') {
            return;
        }
$incident->status = 'acknowledged';
        $incident->acknowledged_at = Carbon::now();
        $incident->save();

        NotificationAuditLog::create([
            'tenant_id' => $incident->tenant_id,
            'actor_user_id' => $actorUserId,
            'target_user_id' => $actorUserId,
            'action' => 'incident_acknowledged',
            'reason' => null,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta' => [
                'incident_id' => $incident->id,
                'status' => 'acknowledged',
            ],
        ]);
    }

    public function resolve(Incident $incident, int $actorUserId, ?string $reason = null): void
    {
        if ($incident->status === 'resolved') {
            return;
        }
$incident->status = 'resolved';
        $incident->resolved_at = Carbon::now();
        $incident->save();

        NotificationAuditLog::create([
            'tenant_id' => $incident->tenant_id,
            'actor_user_id' => $actorUserId,
            'target_user_id' => $actorUserId,
            'action' => 'incident_resolved',
            'reason' => $reason,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta' => [
                'incident_id' => $incident->id,
                'status' => 'resolved',
            ],
        ]);
    }

    /**
     * @param array<string,mixed> $meta
     */
    public function autoResolveByKey(int $tenantId, string $key, array $meta = []): ?Incident
    {
        $incident = Incident::query()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->where('status', '!=', 'resolved')
            ->first();

        if (!$incident) {
            return null;
        }
$incident->status = 'resolved';
        $incident->resolved_at = Carbon::now();
        if (!empty($meta)) {
            $incident->meta = array_merge((array) $incident->meta, $meta);
        }
$incident->save();

        return $incident;
    }

    private function maxSeverity(string $current, string $incoming): string
    {
        $rank = ['info' => 0, 'operational' => 1, 'critical' => 2];
        $currentRank = $rank[$current] ?? 0;
        $incomingRank = $rank[$incoming] ?? 0;

        return $incomingRank > $currentRank ? $incoming : $current;
    }
}
