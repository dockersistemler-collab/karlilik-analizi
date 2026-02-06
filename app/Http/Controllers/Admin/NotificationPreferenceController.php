<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\Marketplace;
use App\Models\NotificationAuditLog;
use App\Models\NotificationPreference;
use App\Models\SupportAccessLog;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class NotificationPreferenceController
{
    public function index(Request $request): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $tenantId = $user->id;

        $marketplaces = Marketplace::query()->orderBy('name')->pluck('name', 'code');
        $preferences = NotificationPreference::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->get()
            ->groupBy(fn ($pref) => ($pref->marketplace ?? 'all'));

        $this->logAudit('view', $tenantId, $user->id, $request, [
            'page' => 'preferences',
        ], false);

        return view('admin.notification-hub.preferences', [
            'marketplaces' => $marketplaces,
            'preferences' => $preferences,
            'types' => [
                NotificationType::Critical->value,
                NotificationType::Operational->value,
                NotificationType::Info->value,
            ],
            'channels' => [
                NotificationChannel::InApp->value,
                NotificationChannel::Email->value,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $validated = $request->validate(['marketplace' => 'nullable|string|max:50',
            'quiet_start' => 'nullable|string|max:5',
            'quiet_end' => 'nullable|string|max:5',
            'quiet_tz' => 'nullable|string|max:64',
            'preferences' => 'array',
        ]);

        $marketplace = $validated['marketplace'] ?? null;
        $quietHours = null;
        if (!empty($validated['quiet_start']) && !empty($validated['quiet_end'])) {
            $quietHours = [
                'start' => $validated['quiet_start'],
                'end' => $validated['quiet_end'],
                'tz' => $validated['quiet_tz'] ?? 'Europe/Istanbul',
            ];
        }
$types = [
            NotificationType::Critical->value,
            NotificationType::Operational->value,
            NotificationType::Info->value,
        ];
        $channels = [
            NotificationChannel::InApp->value,
            NotificationChannel::Email->value,
        ];

        foreach ($types as $type) {
            foreach ($channels as $channel) {
                $enabled = (bool) data_get($validated, "preferences.{$type}.{$channel}", false);
                NotificationPreference::updateOrCreate([
                    'tenant_id' => $user->id,
                    'user_id' => $user->id,
                    'type' => $type,
                    'channel' => $channel,
                    'marketplace' => $marketplace,
                ], [
                    'enabled' => $enabled,
                    'quiet_hours' => $quietHours,
                ]);
            }
        }
$this->logAudit('settings_change', $user->id, $user->id, $request, [
            'marketplace' => $marketplace,
        ], true);

        return back()->with('success', 'Bildirim tercihleri güncellendi.');
    }

    /**
     * @param array<string,mixed> $meta
     */
    private function logAudit(string $action, int $tenantId, int $targetUserId, Request $request, array $meta, bool $requireSupportReason): void
    {
        $reason = $this->resolveSupportReason($request, $requireSupportReason);
        $actorId = session('support_view_actor_user_id') ?: $request->user()?->id;

        NotificationAuditLog::create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actorId,
            'target_user_id' => $targetUserId,
            'action' => $action,
            'reason' => $reason,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => $meta,
        ]);
    }

    private function resolveSupportReason(Request $request, bool $requireInput): ?string
    {
        if (!SupportUser::isEnabled()) {
            return null;
        }
$reason = trim((string) $request->input('support_reason', ''));
        if ($reason === '' && !$requireInput) {
            $logId = session('support_view_log_id');
            if ($logId) {
                $reason = (string) SupportAccessLog::query()->whereKey($logId)->value('reason');
            }
        }

        if ($reason === '') {
            throw ValidationException::withMessages([
                'support_reason' => 'Support View modunda sebep belirtmek zorunludur.',
            ]);
        }

        return $reason;
    }
}