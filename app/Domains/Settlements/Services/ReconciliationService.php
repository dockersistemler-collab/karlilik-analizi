<?php

namespace App\Domains\Settlements\Services;

use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\PayoutRow;
use App\Domains\Settlements\Models\Reconciliation;
use App\Domains\Settlements\Models\LossFinding;
use App\Events\PayoutReconciled;
use App\Domains\Settlements\Support\SettlementDashboardCache;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReconciliationService
{
    private const CHUNK_SIZE = 1000;

    public function __construct(
        private readonly ExpectedFinancialBuilder $expectedBuilder,
        private readonly LossFinderEngine $lossFinder,
        private readonly ConfidenceScoringService $confidenceScoringService,
        private readonly TenantRuleResolver $tenantRuleResolver,
        private readonly DisputeService $disputeService,
        private readonly SettlementDashboardCache $dashboardCache
    ) {
    }

    public function reconcileOne(int $payoutId, ?float $tolerance = null): array
    {
        $payout = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->with('rows')
            ->findOrFail($payoutId);

        $marketplace = (string) ($payout->marketplace ?: 'trendyol');
        $tol = $this->resolveTolerance((int) $payout->tenant_id, $marketplace, $tolerance);
        $runVersion = 2;
        $runHash = $this->buildRunHash($payout, $tol, $runVersion);

        $existingRunCount = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $payout->tenant_id)
            ->where('payout_id', $payout->id)
            ->where('run_hash', $runHash)
            ->count();
        if ($existingRunCount > 0) {
            return [
                'payout_id' => $payout->id,
                'reconciled_rows' => $existingRunCount,
                'run_hash' => $runHash,
                'run_version' => $runVersion,
                'idempotent' => true,
            ];
        }

        $groupedRows = PayoutRow::query()
            ->where('payout_id', $payout->id)
            ->orderBy('id')
            ->get()
            ->groupBy(fn (PayoutRow $row) => $this->matchKey($row));

        $matchedOrderIds = [];
        $results = [];

        if ($groupedRows->isEmpty()) {
            $expectedTotal = round((float) ($payout->expected_amount ?? 0), 2);
            $actualTotal = round((float) ($payout->paid_amount ?? 0), 2);
            $diff = round($actualTotal - $expectedTotal, 2);

            $findings = [];
            if (abs($diff) > $tol) {
                $findings[] = [
                    'code' => $diff < 0 ? 'LOSS_MISSING_IN_PAYOUT' : 'LOSS_UNKNOWN_DEDUCTION',
                    'title' => $diff < 0 ? 'Eksik &Ouml;deme' : 'Beklenmeyen Fark',
                    'detail' => 'Payout satırı olmadan toplam fark tespit edildi.',
                    'severity' => 'high',
                    'amount' => abs($diff),
                    'type' => 'total',
                    'suggested_dispute_type' => $diff < 0 ? 'MISSING_PAYMENT' : 'UNKNOWN_DEDUCTION',
                ];
            }

            $findings = $this->applyConfidenceScores($findings);
            $summary = $this->summarizeFindings($findings);
            $reconciliation = Reconciliation::query()->withoutGlobalScope('tenant_scope')->updateOrCreate(
                [
                    'tenant_id' => $payout->tenant_id,
                    'payout_id' => $payout->id,
                    'match_key' => 'payout:'.$payout->id,
                ],
                [
                    'order_id' => null,
                    'expected_total_net' => $expectedTotal,
                    'actual_total_net' => $actualTotal,
                    'diff_total_net' => $diff,
                    'diff_breakdown_json' => [
                        'total' => [
                            'expected_net' => $expectedTotal,
                            'actual_net' => $actualTotal,
                            'diff_net' => $diff,
                        ],
                    ],
                    'loss_findings_json' => $findings,
                    'findings_summary_json' => $summary,
                    'run_hash' => $runHash,
                    'run_version' => $runVersion,
                    'status' => $this->resolveStatus($diff, $tol, false, false),
                    'tolerance_used' => $tol,
                    'reconciled_at' => now(),
                    'matched_payment_reference' => $payout->payout_reference,
                    'matched_amount' => $actualTotal,
                    'matched_date' => $payout->paid_date,
                    'match_method' => 'PAYOUT_TOTAL',
                    'notes' => 'Fallback reconciliation without payout rows',
                ]
            );

            $this->storeFindings((int) $payout->tenant_id, $reconciliation, $findings);

            if (!empty($findings)) {
                $this->disputeService->createFromFindings(
                    (int) $payout->tenant_id,
                    (int) $payout->id,
                    null,
                    $findings
                );
            }
        }

        foreach ($groupedRows as $key => $rows) {
            $orderNo = $rows->first()?->order_no;
            $order = $this->resolveOrder($payout->tenant_id, (string) $orderNo, (string) $key);
            if ($order) {
                $matchedOrderIds[] = $order->id;
                $this->expectedBuilder->buildForOrder($order, $marketplace);
            }

            $expectedItems = $order ? $order->financialItems()->get() : collect();
            $expectedByType = $this->sumByType($expectedItems, 'net_amount');
            $actualByType = $this->sumByType($rows, 'net_amount');

            $expectedTotal = round(array_sum($expectedByType), 2);
            $actualTotal = round(array_sum($actualByType), 2);
            $diff = round($actualTotal - $expectedTotal, 2);

            $findings = $this->lossFinder->analyze(
                $expectedByType,
                $actualByType,
                $rows->map(fn (PayoutRow $row) => $row->toArray())->all(),
                $expectedItems->map(fn ($item) => $item->toArray())->all(),
                $order !== null,
                $rows->isNotEmpty(),
                $tol,
                (int) $payout->tenant_id,
                $marketplace
            );
            $findings = $this->applyConfidenceScores($findings);

            $status = $this->resolveStatus($diff, $tol, $order !== null, $rows->isNotEmpty());
            $summary = $this->summarizeFindings($findings);

            $reconciliation = Reconciliation::query()
                ->withoutGlobalScope('tenant_scope')
                ->updateOrCreate(
                    [
                        'tenant_id' => $payout->tenant_id,
                        'payout_id' => $payout->id,
                        'match_key' => (string) $key,
                    ],
                    [
                        'order_id' => $order?->id,
                        'expected_total_net' => $expectedTotal,
                        'actual_total_net' => $actualTotal,
                        'diff_total_net' => $diff,
                        'diff_breakdown_json' => $this->buildDiffBreakdown($expectedByType, $actualByType),
                        'loss_findings_json' => $findings,
                        'findings_summary_json' => $summary,
                        'run_hash' => $runHash,
                        'run_version' => $runVersion,
                        'status' => $status,
                        'tolerance_used' => $tol,
                        'reconciled_at' => now(),
                        'matched_payment_reference' => $payout->payout_reference,
                        'matched_amount' => $actualTotal,
                        'matched_date' => $payout->paid_date,
                        'match_method' => 'ORDER_OR_PACKAGE',
                        'notes' => 'Loss Finder reconciliation',
                    ]
                );

            $this->storeFindings((int) $payout->tenant_id, $reconciliation, $findings);

            if (!empty($findings)) {
                $this->disputeService->createFromFindings(
                    (int) $payout->tenant_id,
                    (int) $payout->id,
                    $order?->id,
                    $findings
                );
            }

            $results[] = $reconciliation;
        }

        $this->createMissingInPayoutReconciliations($payout, $matchedOrderIds, $tol, $marketplace, $runHash, $runVersion);

        $this->refreshPayoutStatus($payout, $tol);
        event(new PayoutReconciled((int) $payout->id, (int) $payout->tenant_id, $runHash, (int) $runVersion));
        $this->dashboardCache->forgetAll((int) $payout->tenant_id);

        return [
            'payout_id' => $payout->id,
            'reconciled_rows' => count($results),
            'run_hash' => $runHash,
            'run_version' => $runVersion,
        ];
    }

    public function reconcileByAccount(int $accountId, ?float $tolerance = null): array
    {
        $payoutIds = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('marketplace_account_id', $accountId)
            ->orderBy('id')
            ->pluck('id');

        $count = 0;
        foreach ($payoutIds->chunk(self::CHUNK_SIZE) as $chunk) {
            foreach ($chunk as $payoutId) {
                $this->reconcileOne((int) $payoutId, $tolerance);
                $count++;
            }
        }

        return ['processed_payouts' => $count];
    }

    private function resolveTolerance(int $tenantId, string $marketplace, ?float $provided): float
    {
        if ($provided !== null && $provided >= 0) {
            return round($provided, 4);
        }

        return $this->tenantRuleResolver->tolerance($tenantId, $marketplace, 0.01);
    }

    private function matchKey(PayoutRow $row): string
    {
        if (!empty($row->order_no)) {
            return 'order_no:'.$row->order_no;
        }

        if (!empty($row->package_id)) {
            return 'package_id:'.$row->package_id;
        }

        return 'row:'.$row->id;
    }

    private function resolveOrder(int $tenantId, string $orderNo, string $fallbackKey): ?Order
    {
        if ($orderNo !== '') {
            $order = Order::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($q) use ($orderNo) {
                    $q->where('order_number', $orderNo)
                        ->orWhere('marketplace_order_id', $orderNo);
                })
                ->first();
            if ($order) {
                return $order;
            }
        }

        if (str_starts_with($fallbackKey, 'package_id:')) {
            $package = substr($fallbackKey, 11);
            if ($package !== '') {
                return Order::query()
                    ->where('tenant_id', $tenantId)
                    ->where('tracking_number', $package)
                    ->first();
            }
        }

        return null;
    }

    /**
     * @param  Collection<int,mixed>  $rows
     * @return array<string,float>
     */
    private function sumByType(Collection $rows, string $amountField): array
    {
        $result = [];
        foreach ($rows as $row) {
            $type = (string) data_get($row, 'type', 'other');
            $amount = round((float) data_get($row, $amountField, 0), 2);
            $result[$type] = round(($result[$type] ?? 0.0) + $amount, 2);
        }

        return $result;
    }

    /**
     * @param  array<string,float>  $expected
     * @param  array<string,float>  $actual
     * @return array<string,array<string,float>>
     */
    private function buildDiffBreakdown(array $expected, array $actual): array
    {
        $types = array_unique(array_merge(array_keys($expected), array_keys($actual)));
        $breakdown = [];

        foreach ($types as $type) {
            $e = round((float) ($expected[$type] ?? 0), 2);
            $a = round((float) ($actual[$type] ?? 0), 2);
            $breakdown[$type] = [
                'expected_net' => $e,
                'actual_net' => $a,
                'diff_net' => round($a - $e, 2),
            ];
        }

        return $breakdown;
    }

    private function resolveStatus(float $diff, float $tolerance, bool $hasOrder, bool $hasRows): string
    {
        if ($hasOrder && !$hasRows) {
            return 'missing_in_payout';
        }

        if (!$hasOrder && $hasRows) {
            return 'missing_in_orders';
        }

        if (abs($diff) <= $tolerance) {
            return 'ok';
        }

        return abs($diff) >= 10 ? 'mismatch' : 'warning';
    }

    /**
     * @param  array<int,int>  $matchedOrderIds
     */
    private function createMissingInPayoutReconciliations(
        Payout $payout,
        array $matchedOrderIds,
        float $tolerance,
        string $marketplace,
        string $runHash,
        int $runVersion
    ): void
    {
        $orders = Order::query()
            ->where('tenant_id', $payout->tenant_id)
            ->where('marketplace_account_id', $payout->marketplace_account_id)
            ->whereDate('order_date', '>=', $payout->period_start)
            ->whereDate('order_date', '<=', $payout->period_end)
            ->whereNotIn('id', $matchedOrderIds)
            ->limit(1000)
            ->get();

        foreach ($orders as $order) {
            $this->expectedBuilder->buildForOrder($order, $marketplace);
            $expectedByType = $this->sumByType($order->financialItems()->get(), 'net_amount');
            $expectedTotal = round(array_sum($expectedByType), 2);

            $findings = $this->lossFinder->analyze(
                $expectedByType,
                [],
                [],
                $order->financialItems()->get()->toArray(),
                true,
                false,
                $tolerance,
                (int) $payout->tenant_id,
                $marketplace
            );
            $findings = $this->applyConfidenceScores($findings);
            $summary = $this->summarizeFindings($findings);

            $reconciliation = Reconciliation::query()->withoutGlobalScope('tenant_scope')->updateOrCreate(
                [
                    'tenant_id' => $payout->tenant_id,
                    'payout_id' => $payout->id,
                    'match_key' => 'order_no:'.$order->order_number,
                ],
                [
                    'order_id' => $order->id,
                    'expected_total_net' => $expectedTotal,
                    'actual_total_net' => 0,
                    'diff_total_net' => round(0 - $expectedTotal, 2),
                    'diff_breakdown_json' => $this->buildDiffBreakdown($expectedByType, []),
                    'loss_findings_json' => $findings,
                    'findings_summary_json' => $summary,
                    'run_hash' => $runHash,
                    'run_version' => $runVersion,
                    'status' => 'missing_in_payout',
                    'tolerance_used' => $tolerance,
                    'reconciled_at' => now(),
                    'match_method' => 'ORDER_ONLY',
                    'notes' => 'Order exists in period but payout row not found',
                ]
            );
            $this->storeFindings((int) $payout->tenant_id, $reconciliation, $findings);

            $this->disputeService->createFromFindings(
                (int) $payout->tenant_id,
                (int) $payout->id,
                (int) $order->id,
                $findings
            );
        }
    }

    /**
     * @param  array<int,array<string,mixed>>  $findings
     * @return array<int,array<string,mixed>>
     */
    private function applyConfidenceScores(array $findings): array
    {
        return array_map(function (array $finding): array {
            $finding['confidence_score'] = $this->confidenceScoringService->score($finding);
            return $finding;
        }, $findings);
    }

    /**
     * @param  array<int,array<string,mixed>>  $findings
     * @return array<string,mixed>
     */
    private function summarizeFindings(array $findings): array
    {
        $countsBySeverity = [];
        $countsByType = [];
        $totalAmount = 0.0;
        $confidenceSum = 0.0;

        foreach ($findings as $finding) {
            $severity = strtolower((string) ($finding['severity'] ?? 'low'));
            $type = strtolower((string) ($finding['type'] ?? 'other'));
            $amount = abs((float) ($finding['amount'] ?? 0));
            $confidence = (float) ($finding['confidence_score'] ?? 0);
            $totalAmount += $amount;
            $confidenceSum += $confidence;
            $countsBySeverity[$severity] = (int) ($countsBySeverity[$severity] ?? 0) + 1;
            $countsByType[$type] = (int) ($countsByType[$type] ?? 0) + 1;
        }

        return [
            'count' => count($findings),
            'counts_by_severity' => $countsBySeverity,
            'counts_by_type' => $countsByType,
            'total_amount' => round($totalAmount, 2),
            'avg_confidence' => count($findings) > 0 ? round($confidenceSum / count($findings), 2) : 0,
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $findings
     */
    private function storeFindings(int $tenantId, Reconciliation $reconciliation, array $findings): void
    {
        LossFinding::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where('reconciliation_id', $reconciliation->id)
            ->delete();

        foreach ($findings as $finding) {
            LossFinding::query()->withoutGlobalScope('tenant_scope')->create([
                'tenant_id' => $tenantId,
                'reconciliation_id' => $reconciliation->id,
                'payout_id' => (int) $reconciliation->payout_id,
                'order_id' => $reconciliation->order_id ? (int) $reconciliation->order_id : null,
                'code' => (string) ($finding['code'] ?? 'UNKNOWN'),
                'title' => (string) ($finding['title'] ?? ''),
                'detail' => (string) ($finding['detail'] ?? ''),
                'severity' => (string) ($finding['severity'] ?? 'low'),
                'amount' => round(abs((float) ($finding['amount'] ?? 0)), 2),
                'type' => (string) ($finding['type'] ?? 'other'),
                'suggested_dispute_type' => (string) ($finding['suggested_dispute_type'] ?? 'UNKNOWN_DEDUCTION'),
                'confidence_score' => round((float) ($finding['confidence_score'] ?? 0), 2),
                'confidence' => (int) round((float) ($finding['confidence_score'] ?? 50)),
                'meta' => is_array($finding['meta'] ?? null) ? $finding['meta'] : [],
                'meta_json' => [
                    'run_hash' => $reconciliation->run_hash,
                    'run_version' => $reconciliation->run_version,
                ],
                'occurred_at' => now(),
            ]);
        }
    }

    private function buildRunHash(Payout $payout, float $tolerance, int $runVersion): string
    {
        return hash('sha256', implode('|', [
            (string) $payout->id,
            (string) $payout->tenant_id,
            (string) $payout->updated_at,
            (string) $tolerance,
            $runVersion,
            Str::lower((string) ($payout->marketplace ?? 'trendyol')),
        ]));
    }

    private function refreshPayoutStatus(Payout $payout, float $tolerance): void
    {
        $summary = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $payout->tenant_id)
            ->where('payout_id', $payout->id)
            ->selectRaw('SUM(diff_total_net) as diff_total')
            ->first();

        $diffTotal = round((float) ($summary->diff_total ?? 0), 2);
        $payout->status = abs($diffTotal) <= $tolerance ? 'PAID' : 'DISCREPANCY';
        $payout->save();
    }
}
