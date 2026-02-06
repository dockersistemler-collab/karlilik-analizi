<?php

namespace App\Http\Controllers\Admin;

use App\Models\Incident;
use App\Models\Notification;
use App\Models\NotificationAuditLog;
use App\Models\User;
use App\Services\Features\FeatureGate;
use App\Services\IncidentService;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class IncidentController
{
    public function index(Request $request): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $query = Incident::query()
            ->where('tenant_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('marketplace')) {
            $query->where('marketplace', $request->string('marketplace'));
        }

        if ($request->boolean('unassigned')) {
            $query->whereNull('assigned_to_user_id');
        }
$incidents = $query->orderByDesc('last_seen_at')->paginate(20)->withQueryString();

        $marketplaces = Incident::query()
            ->where('tenant_id', $user->id)
            ->whereNotNull('marketplace')
            ->select('marketplace')
            ->distinct()
            ->orderBy('marketplace')
            ->pluck('marketplace')
            ->all();

        return view('admin.incidents.index', [
            'incidents' => $incidents,
            'incidentSlaEnabled' => app(FeatureGate::class)->enabled('incident_sla', $user),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'marketplace' => $request->string('marketplace')->toString(),
                'unassigned' => $request->boolean('unassigned'),
            ],
            'marketplaces' => $marketplaces,
        ]);
    }

    public function inbox(Request $request): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $incidentSlaEnabled = app(FeatureGate::class)->enabled('incident_sla', $user);
        $filter = $request->string('filter')->toString() ?: 'unassigned';
        $search = trim((string) $request->query('q', ''));

        $query = Incident::query()
            ->where('tenant_id', $user->id)
            ->with('assignedUser');

        if (in_array($filter, ['unassigned', 'sla_risk', 'sla_breach', 'my', 'all_open'], true)) {
            $query->whereIn('status', ['open', 'acknowledged']);
        }

        if ($filter === 'unassigned') {
            $query->whereNull('assigned_to_user_id');
        } elseif ($filter === 'my') {
            $query->where('assigned_to_user_id', $user->id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('marketplace', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%");
            });
        }
$perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        if (in_array($filter, ['sla_risk', 'sla_breach'], true) && $incidentSlaEnabled) {
            $collection = $query->orderByDesc('last_seen_at')->get();
            $collection = $collection->filter(function (Incident $incident) use ($filter): bool {
                return $filter === 'sla_breach'
                    ? $incident->isResolveBreached()
                    : $incident->isAckBreached();
            })->values();

            $items = $collection->slice(($page - 1) * $perPage, $perPage)->values();
            $incidents = new LengthAwarePaginator($items, $collection->count(), $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        } else {
            $incidents = $query->orderByDesc('last_seen_at')->paginate($perPage)->withQueryString();
        }

        return view('admin.incidents.inbox', [
            'incidents' => $incidents,
            'filter' => $filter,
            'search' => $search,
            'incidentSlaEnabled' => $incidentSlaEnabled,
            'supportViewEnabled' => SupportUser::isEnabled(),
        ]);
    }

    public function show(Incident $incident): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);
        $this->authorizeIncident($incident, $user->id);

        $events = $incident->events()
            ->orderByDesc('created_at')
            ->get();

        $notifications = Notification::query()
            ->where('tenant_id', $incident->tenant_id)
            ->where('data->incident_id', $incident->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.incidents.show', [
            'incident' => $incident,
            'events' => $events,
            'notifications' => $notifications,
            'supportViewEnabled' => SupportUser::isEnabled(),
            'incidentSlaEnabled' => app(FeatureGate::class)->enabled('incident_sla', $user),
            'assignableUsers' => User::query()
                ->where('id', $user->id)
                ->select('id', 'name', 'email')
                ->get(),
        ]);
    }

    public function assign(Request $request, Incident $incident): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);
        $this->authorizeIncident($incident, $user->id);

        $data = $request->validate(['assigned_to_user_id' => ['nullable', 'integer'],
        ]);

        $assignedId = $data['assigned_to_user_id'] ?? null;
        if ($assignedId !== null && (int) $assignedId !== (int) $user->id) {
            abort(403);
        }
$incident->assigned_to_user_id = $assignedId;
        $incident->save();

        app(IncidentService::class)->addEvent($incident, 'assignment', 'Incident assigned', [
            'actor_user_id' => $user->id,
            'assigned_to_user_id' => $assignedId,
        ]);

        NotificationAuditLog::create([
            'tenant_id' => $incident->tenant_id,
            'actor_user_id' => $user->id,
            'target_user_id' => $assignedId,
            'action' => 'incident_assigned',
            'reason' => null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => [
                'incident_id' => $incident->id,
                'assigned_to_user_id' => $assignedId,
            ],
        ]);

        return back()->with('success', 'Atama guncellendi.');
    }

    public function assignToMe(Request $request, Incident $incident): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);
        $this->authorizeIncident($incident, $user->id);

        $incident->assigned_to_user_id = $user->id;
        $incident->save();

        app(IncidentService::class)->addEvent($incident, 'assignment', 'Incident assigned to self', [
            'actor_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
        ]);

        NotificationAuditLog::create([
            'tenant_id' => $incident->tenant_id,
            'actor_user_id' => $user->id,
            'target_user_id' => $user->id,
            'action' => 'incident_assigned',
            'reason' => null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => [
                'incident_id' => $incident->id,
                'assigned_to_user_id' => $user->id,
            ],
        ]);

        return back()->with('success', 'Incident size atandi.');
    }

    public function acknowledge(Incident $incident, IncidentService $service): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);
        $this->authorizeIncident($incident, $user->id);

        $service->acknowledge($incident, $user->id);
        $service->addEvent($incident, 'acknowledge', 'Incident acknowledged', [
            'actor_user_id' => $user->id,
        ]);

        return back()->with('success', 'Incident ACK edildi.');
    }

    public function quickAck(Incident $incident, IncidentService $service): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);
        $this->authorizeIncident($incident, $user->id);

        $service->acknowledge($incident, $user->id);
        $service->addEvent($incident, 'acknowledge', 'Incident acknowledged', [
            'actor_user_id' => $user->id,
        ]);

        return back()->with('success', 'Incident ACK edildi.');
    }

    public function resolve(Request $request, Incident $incident, IncidentService $service): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);
        $this->authorizeIncident($incident, $user->id);

        $reason = $request->input('reason');
        $service->resolve($incident, $user->id, is_string($reason) ? $reason : null);
        $service->addEvent($incident, 'resolve', 'Incident resolved', [
            'actor_user_id' => $user->id,
            'reason' => $reason,
        ]);

        return back()->with('success', 'Incident kapatildi.');
    }

    private function authorizeIncident(Incident $incident, int $tenantId): void
    {
        if ((int) $incident->tenant_id !== (int) $tenantId) {
            abort(404);
        }
    }
}
