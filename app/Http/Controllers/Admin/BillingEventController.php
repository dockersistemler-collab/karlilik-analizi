<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingEvent;
use App\Models\BillingEventAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BillingEventController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = trim((string) $request->get('tenant_id', ''));
        $type = trim((string) $request->get('type', ''));
        $status = trim((string) $request->get('status', ''));
        $provider = trim((string) $request->get('provider', ''));
        $correlationId = trim((string) $request->get('correlation_id', ''));
        $invoiceId = trim((string) $request->get('invoice_id', ''));
        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo = trim((string) $request->get('date_to', ''));
        $search = trim((string) $request->get('search', ''));

        $query = BillingEvent::query()
            ->leftJoin('users as tenants', 'billing_events.tenant_id', '=', 'tenants.id')
            ->select('billing_events.*', 'tenants.name as tenant_name', 'tenants.email as tenant_email')
            ->orderByDesc('billing_events.created_at');

        if ($tenantId !== '') {
            $query->where('billing_events.tenant_id', $tenantId);
        }

        if ($type !== '') {
            $query->where('billing_events.type', $type);
        }

        if ($status !== '') {
            $query->where('billing_events.status', $status);
        }

        if ($provider !== '') {
            $query->where('billing_events.provider', $provider);
        }

        if ($correlationId !== '') {
            $query->where('billing_events.correlation_id', $correlationId);
        }

        if ($invoiceId !== '') {
            $query->where('billing_events.invoice_id', $invoiceId);
        }

        if ($dateFrom !== '') {
            $query->whereDate('billing_events.created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('billing_events.created_at', '<=', $dateTo);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('billing_events.correlation_id', 'like', '%'.$search.'%')
                    ->orWhere('billing_events.payload', 'like', '%'.$search.'%');
            });
        }
$events = $query->paginate(20)->withQueryString();

        return view('admin.observability.billing_events.index', [
            'events' => $events,
            'tenantId' => $tenantId,
            'type' => $type,
            'status' => $status,
            'provider' => $provider,
            'correlationId' => $correlationId,
            'invoiceId' => $invoiceId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'search' => $search,
        ]);
    }

    public function show(BillingEvent $billingEvent): View
    {
        $tenant = $billingEvent->tenant_id ? User::query()->find($billingEvent->tenant_id) : null;

        $relatedEvents = collect();
        if ($billingEvent->correlation_id) {
            $relatedEvents = BillingEvent::query()
                ->where('correlation_id', $billingEvent->correlation_id)
                ->orderBy('created_at')
                ->limit(50)
                ->get();
        }
$recentActions = BillingEventAction::query()
            ->with('requestedBy')
            ->where('billing_event_id', $billingEvent->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $canReprocessWebhook = $this->isAllowListed($billingEvent->type, (array) config('observability.reprocess.webhook_allowlist', []));
        $canRetryJob = $this->isAllowListed($billingEvent->type, (array) config('observability.reprocess.job_allowlist', []))
            && $this->hasRetryJobPayload($billingEvent);

        $webhookQueued = $recentActions->firstWhere('action_type', 'reprocess_webhook')?->status === 'queued';
        $jobQueued = $recentActions->firstWhere('action_type', 'retry_job')?->status === 'queued';

        return view('admin.observability.billing_events.show', [
            'event' => $billingEvent,
            'tenant' => $tenant,
            'relatedEvents' => $relatedEvents,
            'recentActions' => $recentActions,
            'canReprocessWebhook' => $canReprocessWebhook,
            'canRetryJob' => $canRetryJob,
            'webhookQueued' => $webhookQueued,
            'jobQueued' => $jobQueued,
        ]);
    }

    private function isAllowListed(?string $type, array $allowList): bool
    {
        $type = $type ?? '';
        foreach ($allowList as $pattern) {
            if (Str::is($pattern, $type)) {
                return true;
            }
        }

        return false;
    }

    private function hasRetryJobPayload(BillingEvent $event): bool
    {
        $payload = is_array($event->payload) ? $event->payload : [];
        if (isset($payload['job_class']) && is_string($payload['job_class']) && class_exists($payload['job_class'])) {
            return true;
        }

        return Str::is('dunning.*', $event->type ?? '');
    }
}
