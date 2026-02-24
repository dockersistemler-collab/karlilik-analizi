<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Settlements\Models\Dispute;
use App\Domains\Settlements\Support\SettlementDashboardCache;
use App\Domains\Settlements\Resources\DisputeResource;
use App\Http\Controllers\Api\V1\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class DisputesController extends Controller
{
    use ResolvesTenant;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Dispute::class);
        $tenantId = $this->currentTenantId();
        $query = Dispute::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->filled('type')) {
            $query->where('dispute_type', (string) $request->query('type'));
        }

        $rows = $query->latest('id')->paginate(20);

        return ApiResponse::success([
            'items' => DisputeResource::collection($rows->getCollection()),
            'pagination' => [
                'total' => $rows->total(),
                'per_page' => $rows->perPage(),
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $tenantId = $this->currentTenantId();
        $row = Dispute::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);
        $this->authorize('view', $row);

        return ApiResponse::success(new DisputeResource($row));
    }

    public function update(Request $request, int $id)
    {
        $tenantId = $this->currentTenantId();
        $row = Dispute::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);
        $this->authorize('update', $row);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:OPEN,IN_REVIEW,SUBMITTED_TO_MARKETPLACE,RESOLVED,REJECTED,open,in_review,resolved,rejected'],
            'assigned_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_key_exists('status', $validated)) {
            $validated['updated_by'] = auth()->id();
        }

        $row->update($validated);
        app(SettlementDashboardCache::class)->forgetAll($tenantId);

        return ApiResponse::success(new DisputeResource($row->fresh()));
    }
}
