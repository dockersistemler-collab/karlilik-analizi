<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Settlements\Models\Dispute;
use App\Domains\Settlements\Models\Payout;
use App\Http\Controllers\Api\V1\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;

class SettlementsDashboardController extends Controller
{
    use ResolvesTenant;

    public function __invoke()
    {
        $tenantId = $this->currentTenantId();
        $query = Payout::query()->withoutGlobalScope('tenant_scope')->where('tenant_id', $tenantId);

        $expected = (float) $query->clone()->sum('expected_amount');
        $paid = (float) $query->clone()->sum('paid_amount');
        $open = (int) Dispute::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['OPEN', 'IN_REVIEW', 'SUBMITTED_TO_MARKETPLACE'])
            ->count();
        $overdue = (int) $query->clone()
            ->whereDate('expected_date', '<', now()->toDateString())
            ->whereNotIn('status', ['PAID'])
            ->count();

        $topDiscrepancies = Dispute::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->orderByRaw('ABS(diff_amount) DESC')
            ->take(5)
            ->get(['id', 'payout_id', 'dispute_type', 'diff_amount', 'status']);

        return ApiResponse::success([
            'expected' => round($expected, 4),
            'paid' => round($paid, 4),
            'open_disputes' => $open,
            'overdue_payouts' => $overdue,
            'top_discrepancies' => $topDiscrepancies,
        ]);
    }
}

