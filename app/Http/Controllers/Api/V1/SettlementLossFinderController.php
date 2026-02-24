<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\Reconciliation;
use App\Domains\Settlements\Support\SettlementDashboardCache;
use App\Domains\Settlements\Services\DisputeService;
use App\Domains\Settlements\Services\PayoutImportService;
use App\Http\Controllers\Api\V1\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateSettlementExportJob;
use App\Jobs\ReconcileAccountPayoutsJob;
use App\Jobs\ReconcileSinglePayoutJob;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettlementLossFinderController extends Controller
{
    use ResolvesTenant;

    public function import(Request $request, PayoutImportService $service)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls'],
            'account_id' => ['required', 'integer', 'exists:marketplace_accounts,id'],
            'marketplace' => ['nullable', 'string', 'max:50'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
        ]);

        $tenantId = $this->currentTenantId();
        $marketplace = (string) ($validated['marketplace'] ?? 'trendyol');

        $result = $service->import(
            $validated['file'],
            $tenantId,
            (int) $validated['account_id'],
            $marketplace,
            $validated['period_start'] ?? null,
            $validated['period_end'] ?? null
        );

        return ApiResponse::success([
            'payout_id' => $result['payout']->id,
            'rows' => $result['rows'],
            'status' => 'imported',
        ], status: 201);
    }

    public function reconcilePayout(Request $request, int $payout)
    {
        $validated = $request->validate([
            'tolerance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tenantId = $this->currentTenantId();
        $payoutModel = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($payout);

        ReconcileSinglePayoutJob::dispatch($tenantId, (int) $payoutModel->id, isset($validated['tolerance']) ? (float) $validated['tolerance'] : null);

        return ApiResponse::success([
            'queued' => true,
            'scope' => 'payout',
            'payout_id' => (int) $payoutModel->id,
        ]);
    }

    public function reconcileAccount(Request $request, int $account)
    {
        $validated = $request->validate([
            'tolerance' => ['nullable', 'numeric', 'min:0'],
        ]);

        ReconcileAccountPayoutsJob::dispatch(
            (int) $account,
            isset($validated['tolerance']) ? (float) $validated['tolerance'] : null
        );

        return ApiResponse::success([
            'queued' => true,
            'scope' => 'account',
            'account_id' => (int) $account,
        ]);
    }

    public function summary(int $payout)
    {
        $tenantId = $this->currentTenantId();

        $payoutModel = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($payout);

        $rows = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where('payout_id', $payoutModel->id)
            ->get();

        $expected = round((float) $rows->sum('expected_total_net'), 2);
        $actual = round((float) $rows->sum('actual_total_net'), 2);
        $diff = round((float) $rows->sum('diff_total_net'), 2);
        $countsByStatus = $rows->groupBy('status')->map->count()->all();

        $breakdown = [];
        $microLoss = 0.0;
        foreach ($rows as $row) {
            $diffBreakdown = is_array($row->diff_breakdown_json) ? $row->diff_breakdown_json : [];
            foreach ($diffBreakdown as $type => $data) {
                $breakdown[$type] = round(($breakdown[$type] ?? 0) + (float) ($data['diff_net'] ?? 0), 2);
            }

            $findings = is_array($row->loss_findings_json) ? $row->loss_findings_json : [];
            foreach ($findings as $finding) {
                if (($finding['code'] ?? '') === 'MICRO_LOSS_AGGREGATOR') {
                    $microLoss += (float) ($finding['amount'] ?? 0);
                }
            }
        }

        return ApiResponse::success([
            'totals' => [
                'expected_total_net' => $expected,
                'actual_total_net' => $actual,
                'diff_total_net' => $diff,
            ],
            'counts_by_status' => $countsByStatus,
            'breakdown_by_type' => $breakdown,
            'micro_loss_total' => round($microLoss, 2),
        ]);
    }

    public function reconciliations(Request $request, int $payout)
    {
        $tenantId = $this->currentTenantId();
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'min_diff' => ['nullable', 'numeric'],
        ]);

        $query = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where('payout_id', $payout)
            ->when(!empty($validated['status']), fn ($q) => $q->where('status', $validated['status']))
            ->when(!empty($validated['search']), function ($q) use ($validated) {
                $search = '%'.$validated['search'].'%';
                $q->where('match_key', 'like', $search);
            })
            ->when(isset($validated['min_diff']), fn ($q) => $q->whereRaw('ABS(diff_total_net) >= ?', [(float) $validated['min_diff']]))
            ->orderByDesc('id');

        return ApiResponse::success($query->paginate(25));
    }

    public function reconciliationDetail(int $id)
    {
        $tenantId = $this->currentTenantId();
        $row = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->with(['order.financialItems', 'payout.rows'])
            ->findOrFail($id);

        return ApiResponse::success([
            'id' => $row->id,
            'status' => $row->status,
            'expected_items' => $row->order?->financialItems ?? [],
            'actual_rows' => $row->payout?->rows ?? [],
            'diff_breakdown' => $row->diff_breakdown_json ?? [],
            'loss_findings' => $row->loss_findings_json ?? [],
        ]);
    }

    public function export(Request $request, int $payout): StreamedResponse|BinaryFileResponse
    {
        $tenantId = $this->currentTenantId();
        $format = strtolower((string) $request->query('format', 'csv'));

        $rows = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where('payout_id', $payout)
            ->orderBy('id')
            ->get();

        if ($format === 'xlsx') {
            $token = (string) Str::uuid();
            // Reuse async job if dataset grows. For now return CSV-compatible file with xlsx extension queue-ready placeholder.
            GenerateSettlementExportJob::dispatch($tenantId, $payout, $token);
            return response()->streamDownload(function () use ($rows): void {
                $out = fopen('php://output', 'wb');
                fputcsv($out, ['match_key', 'status', 'expected_total_net', 'actual_total_net', 'diff_total_net']);
                foreach ($rows as $row) {
                    fputcsv($out, [$row->match_key, $row->status, $row->expected_total_net, $row->actual_total_net, $row->diff_total_net]);
                }
                fclose($out);
            }, "payout-{$payout}-reconciliation.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        }

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['match_key', 'status', 'expected_total_net', 'actual_total_net', 'diff_total_net', 'findings']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->match_key,
                    $row->status,
                    $row->expected_total_net,
                    $row->actual_total_net,
                    $row->diff_total_net,
                    json_encode($row->loss_findings_json, JSON_UNESCAPED_UNICODE),
                ]);
            }
            fclose($out);
        }, "payout-{$payout}-reconciliation.csv", ['Content-Type' => 'text/csv']);
    }

    public function disputesFromFindings(Request $request, DisputeService $service)
    {
        $tenantId = $this->currentTenantId();
        $validated = $request->validate([
            'reconciliation_ids' => ['required', 'array', 'min:1'],
            'reconciliation_ids.*' => ['integer', 'exists:reconciliations,id'],
        ]);

        $rows = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $validated['reconciliation_ids'])
            ->get();

        $created = [];
        foreach ($rows as $row) {
            $findings = is_array($row->loss_findings_json) ? $row->loss_findings_json : [];
            $new = $service->createFromFindings(
                $tenantId,
                (int) $row->payout_id,
                $row->order_id ? (int) $row->order_id : null,
                $findings,
                auth()->id()
            );
            $created = array_merge($created, $new);
        }

        app(SettlementDashboardCache::class)->forgetAll($tenantId);

        return ApiResponse::success([
            'created_count' => count($created),
            'dispute_ids' => collect($created)->pluck('id')->values(),
        ], status: 201);
    }

}
