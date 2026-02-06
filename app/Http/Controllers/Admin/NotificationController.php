<?php

namespace App\Http\Controllers\Admin;

use App\Models\Marketplace;
use App\Models\Notification;
use App\Models\NotificationAuditLog;
use App\Models\SupportAccessLog;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class NotificationController
{
    public function index(Request $request): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);
        $this->authorizeViewAny($user);

        $audienceRole = $request->attributes->get('sub_user') ? 'staff' : 'admin';
        $tenantId = $user->id;

        $query = Notification::query()
            ->forTenant($tenantId)
            ->where('channel', 'in_app')
            ->where(function ($q) use ($user, $audienceRole) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($roleQ) use ($audienceRole) {
                        $roleQ->whereNull('user_id')
                            ->where(function ($audienceQ) use ($audienceRole) {
                                $audienceQ->whereNull('audience_role')
                                    ->orWhere('audience_role', $audienceRole);
                            });
                    });
            });

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('marketplace')) {
            $query->where('marketplace', $request->string('marketplace'));
        }

        if ($request->filled('read')) {
            $read = $request->string('read');
            if ($read === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($read === 'unread') {
                $query->whereNull('read_at');
            }
        }
$from = $request->filled('from') ? $request->date('from') : now()->subDays(30);
        $to = $request->filled('to') ? $request->date('to') : now();

        $query->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        $notifications = $query->latest('created_at')->paginate(20)->withQueryString();

        $marketplaces = Marketplace::query()
            ->orderBy('name')
            ->pluck('name', 'code');

        $this->logAudit('view', $tenantId, $user->id, $request, [
            'filters' => $request->only(['type', 'marketplace', 'read', 'from', 'to']) + [
                'default_from' => $from->toDateString(),
                'default_to' => $to->toDateString(),
            ],
        ], false);

        return view('admin.notification-hub.index', [
            'notifications' => $notifications,
            'marketplaces' => $marketplaces,
            'defaultFrom' => $from->toDateString(),
            'defaultTo' => $to->toDateString(),
        ]);
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);
        $this->authorizeNotification($user, $notification);

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }
$this->logAudit('mark_read', $notification->tenant_id, $user->id, $request, [
            'notification_id' => $notification->id,
        ], true);

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);
        $this->authorizeViewAny($user);

        $audienceRole = $request->attributes->get('sub_user') ? 'staff' : 'admin';
        $tenantId = $user->id;

        Notification::query()
            ->forTenant($tenantId)
            ->where('channel', 'in_app')
            ->whereNull('read_at')
            ->where(function ($q) use ($user, $audienceRole) {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($roleQ) use ($audienceRole) {
                        $roleQ->whereNull('user_id')
                            ->where(function ($audienceQ) use ($audienceRole) {
                                $audienceQ->whereNull('audience_role')
                                    ->orWhere('audience_role', $audienceRole);
                            });
                    });
            })
            ->update(['read_at' => now()]);

        $this->logAudit('mark_read', $tenantId, $user->id, $request, [
            'action' => 'read_all',
        ], true);

        return back()->with('success', 'Tüm bildirimler okundu olarak işaretlendi.');
    }

    private function authorizeViewAny($user): void
    {
        if (!$user->isClient() && !$user->isSuperAdmin() && $user->role !== 'support_agent') {
            abort(403);
        }
    }

    private function authorizeNotification($user, Notification $notification): void
    {
        if ((int) $notification->tenant_id !== (int) $user->id) {
            abort(404);
        }
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
