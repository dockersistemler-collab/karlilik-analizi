<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Marketplace;
use App\Models\Notification;
use App\Models\NotificationAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController
{
    public function index(Request $request): View
    {
        $selectedUserId = $request->query('user_id');
        $selectedUser = null;

        if ($selectedUserId) {
            $selectedUser = User::query()->whereKey($selectedUserId)->first();
        }

        if (!$selectedUser) {
            $selectedUser = $request->user();
        }
$tenantId = $selectedUser?->id ?? 0;

        $query = Notification::query()
            ->forTenant($tenantId)
            ->where('channel', 'in_app');

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
        $marketplaces = Marketplace::query()->orderBy('name')->pluck('name', 'code');
        $tenants = User::query()->where('role', 'client')->orderBy('name')->get();

        NotificationAuditLog::create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $request->user()?->id, 'target_user_id' => $selectedUser?->id, 'action' => 'view',
            'reason' => null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => [
                'filters' => $request->only(['type', 'marketplace', 'read', 'from', 'to']) + [
                    'default_from' => $from->toDateString(),
                    'default_to' => $to->toDateString(),
                ],
            ],
        ]);

        return view('super-admin.notification-hub.index', [
            'notifications' => $notifications,
            'marketplaces' => $marketplaces,
            'tenants' => $tenants,
            'selectedUser' => $selectedUser,
            'defaultFrom' => $from->toDateString(),
            'defaultTo' => $to->toDateString(),
        ]);
    }
}
