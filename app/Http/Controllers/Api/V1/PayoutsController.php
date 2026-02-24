<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Repositories\PayoutRepositoryInterface;
use App\Domains\Settlements\Resources\PayoutResource;
use App\Domains\Settlements\Resources\PayoutTransactionResource;
use App\Http\Controllers\Api\V1\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Jobs\ReconcileSinglePayoutJob;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayoutsController extends Controller
{
    use ResolvesTenant;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Payout::class);
        $tenantId = $this->currentTenantId();
        $rows = app(PayoutRepositoryInterface::class)->paginateForTenant($tenantId, $request->all(), 20);

        return ApiResponse::success([
            'items' => PayoutResource::collection($rows->getCollection()),
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
        $row = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->with(['transactions', 'reconciliation', 'disputes'])
            ->findOrFail($id);
        $this->authorize('view', $row);

        return ApiResponse::success(new PayoutResource($row));
    }

    public function transactions(int $id)
    {
        $tenantId = $this->currentTenantId();
        $row = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->with('transactions')
            ->findOrFail($id);
        $this->authorize('view', $row);

        return ApiResponse::success(PayoutTransactionResource::collection($row->transactions));
    }

    public function reconcile(int $id)
    {
        $tenantId = $this->currentTenantId();
        $row = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);
        $this->authorize('reconcile', $row);

        ReconcileSinglePayoutJob::dispatch((int) $tenantId, (int) $row->id);

        return ApiResponse::success([
            'queued' => true,
            'payout_id' => (int) $row->id,
        ]);
    }

    public function export(int $id): StreamedResponse
    {
        $tenantId = $this->currentTenantId();
        $payout = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->with('transactions')
            ->findOrFail($id);
        $this->authorize('export', $payout);

        $filename = "payout-{$payout->id}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($payout): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['type', 'reference_id', 'amount', 'vat_amount']);
            foreach ($payout->transactions as $tx) {
                fputcsv($out, [$tx->type, $tx->reference_id, $tx->amount, $tx->vat_amount]);
            }
            fclose($out);
        }, 200, $headers);
    }
}
