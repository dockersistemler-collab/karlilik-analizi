<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Settlements\Models\Dispute;
use App\Domains\Settlements\Models\FeatureFlag;
use App\Domains\Settlements\Models\Payout;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SettlementCenterController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $status = (string) $request->string('status', '');
        $payouts = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->with(['account:id,store_name', 'integration:id,code,name'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('period_end')
            ->paginate(20)
            ->withQueryString();

        return view('admin.settlements.index', compact('payouts', 'status'));
    }

    public function show(int $payout): View
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $payoutModel = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->with(['account:id,store_name', 'integration:id,code,name', 'transactions', 'disputes'])
            ->findOrFail($payout);

        return view('admin.settlements.show', ['payout' => $payoutModel]);
    }

    public function disputes(Request $request): View
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $status = (string) $request->string('status', '');
        $disputes = Dispute::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->with(['payout:id,payout_reference,period_start,period_end,currency', 'assignee:id,name'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.settlements.disputes', compact('disputes', 'status'));
    }

    private function resolveTenantId(): int
    {
        $subUser = auth('subuser')->user();
        $owner = $subUser ? $subUser->owner : auth()->user();

        $tenantId = (int) ($owner->tenant_id ?: $owner->id);
        abort_if($tenantId <= 0, 400, 'Tenant context is missing.');

        return $tenantId;
    }

    private function ensureEnabled(int $tenantId): void
    {
        abort_if(!Schema::hasTable('feature_flags'), 403, 'Feature flags are not initialized.');

        $enabled = FeatureFlag::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where('key', 'hakedis_module')
            ->value('enabled');

        abort_if(!$enabled, 403, 'Hakediş modülü aktif değil.');
    }
}
