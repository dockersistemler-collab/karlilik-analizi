<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\SettlementRule;
use App\Http\Controllers\Api\V1\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SettlementRulesController extends Controller
{
    use ResolvesTenant;

    public function index()
    {
        $tenantId = $this->currentTenantId();
        $rows = SettlementRule::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->with('integration')
            ->get();

        return ApiResponse::success($rows);
    }

    public function store(Request $request)
    {
        $tenantId = $this->currentTenantId();
        $validated = $request->validate([
            'marketplace' => ['required', 'in:trendyol,hepsiburada,n11,amazon'],
            'ruleset' => ['required', 'array'],
        ]);

        $integration = MarketplaceIntegration::query()
            ->where('code', $validated['marketplace'])
            ->firstOrFail();

        $row = SettlementRule::query()
            ->withoutGlobalScope('tenant_scope')
            ->updateOrCreate(
                ['tenant_id' => $tenantId, 'marketplace_integration_id' => $integration->id],
                ['ruleset' => $validated['ruleset']]
            );

        return ApiResponse::success($row, status: 201);
    }

    public function show(int $id)
    {
        $tenantId = $this->currentTenantId();
        $row = SettlementRule::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        return ApiResponse::success($row);
    }

    public function update(Request $request, int $id)
    {
        $tenantId = $this->currentTenantId();
        $row = SettlementRule::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $validated = $request->validate([
            'ruleset' => ['required', 'array'],
        ]);

        $row->update(['ruleset' => $validated['ruleset']]);

        return ApiResponse::success($row->fresh());
    }

    public function destroy(int $id)
    {
        $tenantId = $this->currentTenantId();
        $row = SettlementRule::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $row->delete();
        return ApiResponse::success(['deleted' => true]);
    }
}

