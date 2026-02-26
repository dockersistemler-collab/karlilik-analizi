<?php

namespace App\Domains\Settlements\Services;

use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\Reconciliation;

class ReconcileRegressionGuardService
{
    /**
     * @return array{regression_flag:bool,regression_note:string,comparison:array<string,mixed>}
     */
    public function evaluateAndPersist(Payout $payout, ?string $runHash): array
    {
        $current = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $payout->tenant_id)
            ->where('payout_id', $payout->id)
            ->where('run_hash', $runHash)
            ->get();

        $latestCurrent = $current->sortByDesc('id')->first();
        $previousRunVersion = max(((int) ($latestCurrent?->run_version ?? 1)) - 1, 1);

        $previous = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $payout->tenant_id)
            ->where('payout_id', $payout->id)
            ->where(function ($q) use ($runHash, $previousRunVersion): void {
                if ($runHash) {
                    $q->where('run_hash', '!=', $runHash);
                }
                $q->orWhere('run_version', (string) $previousRunVersion);
            })
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $currentMismatch = $current->whereIn('status', ['mismatch', 'missing_in_payout', 'missing_in_orders'])->count();
        $prevMismatch = $previous->whereIn('status', ['mismatch', 'missing_in_payout', 'missing_in_orders'])->count();

        $currentDiff = round((float) $current->sum('diff_total_net'), 2);
        $prevDiff = round((float) $previous->sum('diff_total_net'), 2);
        $currentHigh = $this->highSeverityCount($current);
        $prevHigh = $this->highSeverityCount($previous);

        $mismatchIncreasePct = $this->increasePct($prevMismatch, $currentMismatch);
        $diffIncreasePct = $this->increasePct(abs($prevDiff), abs($currentDiff));
        $highIncreasePct = $this->increasePct($prevHigh, $currentHigh);

        $flag = $mismatchIncreasePct > 50 || $diffIncreasePct > 50;
        $note = $flag
            ? "Regression risk detected (mismatch +{$mismatchIncreasePct}%, diff +{$diffIncreasePct}%, high +{$highIncreasePct}%)"
            : 'No regression detected';

        $payout->regression_flag = $flag;
        $payout->regression_note = $note;
        $payout->regression_checked_at = now();
        $payout->save();

        return [
            'regression_flag' => $flag,
            'regression_note' => $note,
            'comparison' => [
                'previous' => [
                    'mismatch_count' => $prevMismatch,
                    'total_diff' => $prevDiff,
                    'high_severity_findings' => $prevHigh,
                ],
                'current' => [
                    'mismatch_count' => $currentMismatch,
                    'total_diff' => $currentDiff,
                    'high_severity_findings' => $currentHigh,
                ],
                'increase_pct' => [
                    'mismatch' => $mismatchIncreasePct,
                    'total_diff' => $diffIncreasePct,
                    'high_severity' => $highIncreasePct,
                ],
            ],
        ];
    }

    private function increasePct(float|int $previous, float|int $current): float
    {
        $previous = (float) $previous;
        $current = (float) $current;
        if ($previous <= 0 && $current > 0) {
            return 100.0;
        }
        if ($previous <= 0) {
            return 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * @param  \Illuminate\Support\Collection<int,Reconciliation>  $rows
     */
    private function highSeverityCount($rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $findings = is_array($row->loss_findings_json) ? $row->loss_findings_json : [];
            foreach ($findings as $finding) {
                if (strtolower((string) ($finding['severity'] ?? '')) === 'high') {
                    $count++;
                }
            }
        }

        return $count;
    }
}
