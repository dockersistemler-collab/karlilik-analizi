<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Settlements\Models\Dispute;
use App\Domains\Settlements\Models\FeatureFlag;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\Reconciliation;
use App\Domains\Settlements\Services\DisputeService;
use App\Domains\Settlements\Support\SettlementDashboardCache;
use App\Domains\Settlements\Support\SettlementExportStateStore;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateSettlementExportJob;
use App\Jobs\ReconcileSinglePayoutJob;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettlementCenterController extends Controller
{
    private const ASYNC_EXPORT_TX_THRESHOLD = 1000;

    public function index(Request $request): View
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $status = (string) $request->string('status', '');
        $summary = app(SettlementDashboardCache::class)->rememberPortalSummary($tenantId, function () use ($tenantId): array {
            $baseQuery = Payout::query()
                ->withoutGlobalScope('tenant_scope')
                ->where('tenant_id', $tenantId);

            return [
                'expected_total' => (float) (clone $baseQuery)->sum('expected_amount'),
                'paid_total' => (float) (clone $baseQuery)->sum('paid_amount'),
                'total_diff' => (float) Reconciliation::query()
                    ->withoutGlobalScope('tenant_scope')
                    ->where('tenant_id', $tenantId)
                    ->sum('diff_total_net'),
                'discrepancy_count' => (int) (clone $baseQuery)->where('status', 'DISCREPANCY')->count(),
                'open_disputes' => (int) Dispute::query()
                    ->withoutGlobalScope('tenant_scope')
                    ->where('tenant_id', $tenantId)
                    ->whereIn('status', ['OPEN', 'IN_REVIEW', 'SUBMITTED_TO_MARKETPLACE', 'open', 'in_review'])
                    ->count(),
                'overdue_payouts' => (int) (clone $baseQuery)
                    ->whereDate('expected_date', '<', now()->toDateString())
                    ->whereNotIn('status', ['PAID', 'paid'])
                    ->count(),
                'last_reconciled_at' => Reconciliation::query()
                    ->withoutGlobalScope('tenant_scope')
                    ->where('tenant_id', $tenantId)
                    ->max('updated_at'),
            ];
        });

        $payouts = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->with(['account:id,store_name', 'integration:id,code,name'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('period_end')
            ->paginate(20)
            ->withQueryString();

        return view('admin.settlements.index', compact('payouts', 'status', 'summary'));
    }

    public function show(int $payout): View
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $payoutModel = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->with(['account:id,store_name', 'integration:id,code,name', 'transactions', 'disputes', 'reconciliation', 'reconciliations'])
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

    public function reconcile(int $payout): RedirectResponse
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $payoutModel = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($payout);

        ReconcileSinglePayoutJob::dispatch($tenantId, (int) $payoutModel->id);

        return redirect()
            ->route('portal.settlements.show', $payoutModel->id)
            ->with('success', 'Hakediş mutabakatı kuyruğa alındı.');
    }

    public function export(Request $request, int $payout): StreamedResponse|RedirectResponse
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $payoutModel = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->with('transactions')
            ->findOrFail($payout);
        $format = strtolower((string) $request->query('format', 'csv'));

        $transactionCount = (int) $payoutModel->transactions()->count();
        if ($transactionCount > self::ASYNC_EXPORT_TX_THRESHOLD) {
            $token = (string) Str::uuid();
            app(SettlementExportStateStore::class)->putQueued($token, $tenantId, (int) $payoutModel->id);

            GenerateSettlementExportJob::dispatch($tenantId, (int) $payoutModel->id, $token);

            return redirect()
                ->route('portal.settlements.exports.show', ['token' => $token])
                ->with('info', 'Büyük export kuyruğa alındı. Hazır olduğunda bu sayfadan indirebilirsiniz.');
        }

        $ext = $format === 'xlsx' ? 'xlsx' : 'csv';
        $filename = 'settlement-'.$payoutModel->id.'.'.$ext;
        $contentType = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv';

        return response()->streamDownload(function () use ($payoutModel): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['type', 'reference_id', 'amount', 'vat_amount']);
            foreach ($payoutModel->transactions as $tx) {
                fputcsv($handle, [$tx->type, $tx->reference_id, $tx->amount, $tx->vat_amount]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => $contentType]);
    }

    public function exportStatus(string $token): View
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $state = app(SettlementExportStateStore::class)->get($token);
        abort_if(!$state || (int) ($state['tenant_id'] ?? 0) !== $tenantId, 404);

        return view('admin.settlements.export-status', [
            'state' => $state,
            'token' => $token,
        ]);
    }

    public function exportDownload(string $token): BinaryFileResponse
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $state = app(SettlementExportStateStore::class)->get($token);
        abort_if(!$state || (int) ($state['tenant_id'] ?? 0) !== $tenantId, 404);
        abort_if(($state['status'] ?? '') !== 'ready', 404);

        $path = (string) ($state['file_path'] ?? '');
        $filename = (string) ($state['filename'] ?? 'settlement-export.csv');
        abort_if($path === '' || !Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $filename, ['Content-Type' => 'text/csv']);
    }

    public function updateDispute(Request $request, int $dispute): RedirectResponse
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $disputeModel = Dispute::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->findOrFail($dispute);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['OPEN', 'IN_REVIEW', 'SUBMITTED_TO_MARKETPLACE', 'RESOLVED', 'REJECTED', 'open', 'in_review', 'resolved', 'rejected'])],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $disputeModel->update($validated);
        app(SettlementDashboardCache::class)->forgetAll($tenantId);

        return redirect()
            ->route('portal.settlements.disputes')
            ->with('success', 'Sapma kaydı güncellendi.');
    }

    public function createDisputesFromFindings(Request $request, int $payout, DisputeService $service): RedirectResponse
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $validated = $request->validate([
            'reconciliation_ids' => ['required', 'array', 'min:1'],
            'reconciliation_ids.*' => ['integer', 'exists:reconciliations,id'],
        ]);

        $rows = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where('payout_id', $payout)
            ->whereIn('id', $validated['reconciliation_ids'])
            ->get();

        $count = 0;
        foreach ($rows as $row) {
            $findings = is_array($row->loss_findings_json) ? $row->loss_findings_json : [];
            $created = $service->createFromFindings(
                $tenantId,
                (int) $row->payout_id,
                $row->order_id ? (int) $row->order_id : null,
                $findings,
                auth()->id()
            );
            $count += count($created);
        }

        app(SettlementDashboardCache::class)->forgetAll($tenantId);

        return redirect()
            ->route('portal.settlements.show', $payout)
            ->with('success', "{$count} adet dispute oluşturuldu.");
    }

    public function bulkUpdateDisputes(Request $request): RedirectResponse
    {
        $tenantId = $this->resolveTenantId();
        $this->ensureEnabled($tenantId);

        $validated = $request->validate([
            'dispute_ids' => ['required', 'array', 'min:1'],
            'dispute_ids.*' => ['integer', 'exists:disputes,id'],
            'status' => ['required', Rule::in(['open', 'in_review', 'resolved', 'rejected', 'OPEN', 'IN_REVIEW', 'RESOLVED', 'REJECTED'])],
        ]);

        Dispute::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $validated['dispute_ids'])
            ->update([
                'status' => $validated['status'],
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        app(SettlementDashboardCache::class)->forgetAll($tenantId);

        return redirect()
            ->route('portal.settlements.disputes')
            ->with('success', 'Seçilen dispute kayıtları güncellendi.');
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
