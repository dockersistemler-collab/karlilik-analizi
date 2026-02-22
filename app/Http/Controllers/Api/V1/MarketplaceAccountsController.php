<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Marketplaces\Services\MarketplaceConnectorRegistry;
use App\Http\Controllers\Api\V1\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MarketplaceAccountsController extends Controller
{
    use ResolvesTenant;

    public function index()
    {
        $this->authorize('viewAny', MarketplaceAccount::class);
        $tenantId = $this->currentTenantId();
        $rows = MarketplaceAccount::query()->where('tenant_id', $tenantId)->paginate(20);

        return ApiResponse::success($rows);
    }

    public function store(Request $request)
    {
        $this->authorize('create', MarketplaceAccount::class);
        $tenantId = $this->currentTenantId();

        $validated = $request->validate([
            'marketplace' => ['required', 'in:trendyol,hepsiburada,n11,amazon'],
            'store_name' => ['required', 'string', 'max:200'],
            'credentials' => ['required', 'array'],
            'status' => ['nullable', 'in:active,passive'],
        ]);

        $integration = MarketplaceIntegration::query()
            ->where('code', $validated['marketplace'])
            ->first();

        $row = MarketplaceAccount::query()->create([
            'tenant_id' => $tenantId,
            'marketplace_integration_id' => $integration?->id,
            'marketplace' => $validated['marketplace'],
            'connector_key' => $validated['marketplace'],
            'store_name' => $validated['store_name'],
            'credentials' => $validated['credentials'],
            'status' => $validated['status'] ?? 'active',
            'is_active' => ($validated['status'] ?? 'active') === 'active',
        ]);

        return ApiResponse::success($row, status: 201);
    }

    public function show(int $id)
    {
        $tenantId = $this->currentTenantId();
        $row = MarketplaceAccount::query()->where('tenant_id', $tenantId)->findOrFail($id);
        $this->authorize('view', $row);

        return ApiResponse::success($row);
    }

    public function update(Request $request, int $id)
    {
        $tenantId = $this->currentTenantId();
        $row = MarketplaceAccount::query()->where('tenant_id', $tenantId)->findOrFail($id);
        $this->authorize('update', $row);

        $validated = $request->validate([
            'store_name' => ['sometimes', 'string', 'max:200'],
            'credentials' => ['sometimes', 'array'],
            'status' => ['sometimes', 'in:active,passive'],
        ]);

        if (isset($validated['status'])) {
            $validated['is_active'] = $validated['status'] === 'active';
        }

        $row->update($validated);

        return ApiResponse::success($row->fresh());
    }

    public function destroy(int $id)
    {
        $tenantId = $this->currentTenantId();
        $row = MarketplaceAccount::query()->where('tenant_id', $tenantId)->findOrFail($id);
        $this->authorize('delete', $row);
        $row->delete();

        return ApiResponse::success(['deleted' => true]);
    }

    public function amazonPing(int $id, MarketplaceConnectorRegistry $registry)
    {
        $tenantId = $this->currentTenantId();
        $account = MarketplaceAccount::query()->where('tenant_id', $tenantId)->findOrFail($id);
        $this->authorize('view', $account);

        if (strtolower((string) $account->marketplace) !== 'amazon') {
            return ApiResponse::problem('Invalid marketplace', 'This endpoint supports only amazon accounts.', 422);
        }

        $connector = $registry->resolve($account);
        $sample = $connector->fetchOrders(now()->subDay(), now(), [], 0, 50);

        return ApiResponse::success([
            'ok' => true,
            'sample_count' => count($sample['items'] ?? []),
        ]);
    }
}
