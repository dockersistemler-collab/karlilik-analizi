<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\LossFinding;
use App\Domains\Settlements\Models\LossPattern;
use App\Domains\Settlements\Models\Reconciliation;
use App\Domains\Settlements\Models\ReconciliationRule;
use App\Domains\Settlements\Support\SettlementDashboardCache;
use App\Domains\Settlements\Services\DisputeService;
use App\Domains\Settlements\Services\PayoutImportService;
use App\Domains\Settlements\Services\ReconcileRegressionGuardService;
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

    public function findings(Request $request, int $payout)
    {
        $tenantId = $this->currentTenantId();
        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'severity' => ['nullable', 'string', 'in:low,medium,high'],
            'min_confidence' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'type' => ['nullable', 'string'],
            'suggested_dispute_type' => ['nullable', 'string'],
        ]);

        $query = LossFinding::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where('payout_id', $payout)
            ->when(!empty($validated['code']), fn ($q) => $q->where('code', (string) $validated['code']))
            ->when(!empty($validated['severity']), fn ($q) => $q->where('severity', (string) $validated['severity']))
            ->when(!empty($validated['type']), fn ($q) => $q->where('type', (string) $validated['type']))
            ->when(!empty($validated['suggested_dispute_type']), fn ($q) => $q->where('suggested_dispute_type', (string) $validated['suggested_dispute_type']))
            ->when(isset($validated['min_confidence']), fn ($q) => $q->where('confidence', '>=', (int) $validated['min_confidence']))
            ->when(isset($validated['min_amount']), fn ($q) => $q->where('amount', '>=', (float) $validated['min_amount']))
            ->orderByDesc('confidence')
            ->orderByDesc('id');

        return ApiResponse::success($query->paginate(25));
    }

    public function patterns(Request $request, int $payout)
    {
        $tenantId = $this->currentTenantId();
        $sort = (string) $request->query('sort', 'total_amount');
        $limit = min(max((int) $request->query('limit', 50), 1), 200);
        if (!in_array($sort, ['total_amount', 'occurrences', 'last_seen_at'], true)) {
            $sort = 'total_amount';
        }

        $rows = LossPattern::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($payout): void {
                $q->where('payout_id', $payout)->orWhereNull('payout_id');
            })
            ->orderByDesc($sort)
            ->limit($limit)
            ->get();

        return ApiResponse::success($rows);
    }

    public function regression(int $payout, ReconcileRegressionGuardService $service)
    {
        $tenantId = $this->currentTenantId();
        $payoutModel = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($payout);

        $runHash = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where('payout_id', $payoutModel->id)
            ->orderByDesc('id')
            ->value('run_hash');

        $result = $service->evaluateAndPersist($payoutModel, $runHash);

        return ApiResponse::success([
            'payout_id' => (int) $payoutModel->id,
            'run_hash' => $runHash,
            'regression_flag' => (bool) $result['regression_flag'],
            'regression_note' => (string) $result['regression_note'],
            'regression_checked_at' => optional($payoutModel->fresh()->regression_checked_at)->toISOString(),
            'comparison' => $result['comparison'] ?? [],
        ]);
    }

    public function upsertTenantRule(Request $request)
    {
        $tenantId = $this->currentTenantId();
        $validated = $request->validate([
            'marketplace' => ['required', 'string', 'max:50'],
            'rule_type' => ['required', 'string', 'max:50'],
            'key' => ['required', 'string', 'max:120'],
            'value' => ['required', 'array'],
            'priority' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $rule = ReconciliationRule::query()
            ->withoutGlobalScope('tenant_scope')
            ->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'scope_type' => 'tenant',
                    'marketplace' => $validated['marketplace'],
                    'rule_type' => $validated['rule_type'],
                    'key' => $validated['key'],
                ],
                [
                    'scope_key' => "tenant:{$tenantId}",
                    'scope' => 'tenant',
                    'value' => $validated['value'],
                    'priority' => (int) ($validated['priority'] ?? 0),
                    'is_active' => (bool) ($validated['is_active'] ?? true),
                    'valid_from' => $validated['valid_from'] ?? null,
                    'valid_to' => $validated['valid_to'] ?? null,
                ]
            );

        return ApiResponse::success($rule);
    }

    public function upsertTenantScopedRule(Request $request, int $tenant)
    {
        $validated = $request->validate([
            'marketplace' => ['required', 'string', 'max:50'],
            'rule_type' => ['required', 'in:map_row_type,tolerance,loss_rule'],
            'key' => ['required', 'string', 'max:120'],
            'value' => ['required', 'array'],
            'priority' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $rule = ReconciliationRule::query()
            ->withoutGlobalScope('tenant_scope')
            ->updateOrCreate(
                [
                    'tenant_id' => $tenant,
                    'scope' => 'tenant',
                    'scope_type' => 'tenant',
                    'marketplace' => $validated['marketplace'],
                    'rule_type' => $validated['rule_type'],
                    'key' => $validated['key'],
                ],
                [
                    'scope_key' => "tenant:{$tenant}",
                    'value' => $validated['value'],
                    'priority' => (int) ($validated['priority'] ?? 0),
                    'is_active' => (bool) ($validated['is_active'] ?? true),
                    'valid_from' => $validated['valid_from'] ?? null,
                    'valid_to' => $validated['valid_to'] ?? null,
                ]
            );

        return ApiResponse::success($rule, status: 201);
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
