<?php

namespace App\Domains\Settlements\Services;

use App\Domains\Settlements\Models\LossFinding;
use App\Domains\Settlements\Models\LossPattern;
use App\Domains\Settlements\Models\Payout;
use Illuminate\Support\Carbon;

class LossPatternAggregatorService
{
    /**
     * @return array<int,LossPattern>
     */
    public function aggregateForPayout(int $tenantId, int $payoutId, ?string $runHash, int $runVersion = 2): array
    {
        $payout = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->find($payoutId);
        $marketplace = (string) ($payout?->marketplace ?? 'trendyol');

        $findings = LossFinding::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where('payout_id', $payoutId)
            ->get();

        if ($findings->isEmpty()) {
            return [];
        }

        $groups = $findings->groupBy(function (LossFinding $finding) use ($tenantId, $marketplace): string {
            return $this->patternKey(
                $tenantId,
                $marketplace,
                (string) $finding->code,
                (string) ($finding->type ?? 'other')
            );
        });

        $result = [];
        foreach ($groups as $patternKey => $rows) {
            /** @var LossFinding $first */
            $first = $rows->first();
            $currentCount = $rows->count();
            $currentTotal = round((float) $rows->sum('amount'), 2);
            $currentAvgConfidence = round((float) $rows->avg('confidence_score'), 2);
            $firstSeen = $rows->min('created_at');
            $lastSeen = $rows->max('updated_at');

            $pattern = LossPattern::query()
                ->withoutGlobalScope('tenant_scope')
                ->firstOrNew([
                    'tenant_id' => $tenantId,
                    'pattern_key' => $patternKey,
                ]);

            $previousOccurrences = (int) ($pattern->occurrences ?? 0);
            $previousTotalAmount = (float) ($pattern->total_amount ?? 0);

            $pattern->marketplace = $marketplace;
            $pattern->payout_id = $payoutId;
            $pattern->run_hash = $runHash;
            $pattern->run_version = $runVersion;
            $pattern->code = (string) $first->code;
            $pattern->type = (string) ($first->type ?? 'other');
            $pattern->finding_code = (string) $first->code;
            $pattern->severity = (string) ($first->severity ?? 'low');
            $pattern->occurrences = $previousOccurrences + $currentCount;
            $pattern->occurrence_count = $pattern->occurrences;
            $pattern->total_amount = round($previousTotalAmount + $currentTotal, 2);
            $pattern->avg_confidence = $currentAvgConfidence;
            $pattern->first_seen_at = $pattern->first_seen_at ?: ($firstSeen ? Carbon::parse($firstSeen) : now());
            $pattern->last_seen_at = $lastSeen ? Carbon::parse($lastSeen) : now();
            $pattern->sample_finding_id = $first->id;
            $pattern->meta = [
                'examples' => $rows->take(3)->map(fn (LossFinding $f) => [
                    'id' => $f->id,
                    'amount' => (float) $f->amount,
                    'confidence' => (int) ($f->confidence ?? round((float) $f->confidence_score)),
                ])->values()->all(),
            ];
            $pattern->examples_json = $pattern->meta;
            $pattern->save();

            $rows->each(function (LossFinding $finding) use ($patternKey): void {
                $finding->pattern_key = $patternKey;
                $finding->save();
            });

            $result[] = $pattern;
        }

        $this->aggregateMicroSegment($tenantId, $payoutId, $marketplace, $runHash, $runVersion);

        return $result;
    }

    public function patternKey(int $tenantId, string $marketplace, string $code, string $type, ?string $channel = null): string
    {
        return sha1(implode('|', [
            $tenantId,
            strtolower($marketplace),
            strtoupper($code),
            strtolower($type),
            strtolower((string) $channel),
        ]));
    }

    private function aggregateMicroSegment(int $tenantId, int $payoutId, string $marketplace, ?string $runHash, int $runVersion): void
    {
        $microRows = LossFinding::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where('payout_id', $payoutId)
            ->whereBetween('amount', [0.01, 2.00])
            ->get();

        if ($microRows->count() < 2) {
            return;
        }

        $patternKey = $this->patternKey($tenantId, $marketplace, 'MICRO_LOSS', 'micro');
        $pattern = LossPattern::query()
            ->withoutGlobalScope('tenant_scope')
            ->firstOrNew([
                'tenant_id' => $tenantId,
                'pattern_key' => $patternKey,
            ]);

        $pattern->marketplace = $marketplace;
        $pattern->payout_id = $payoutId;
        $pattern->run_hash = $runHash;
        $pattern->run_version = $runVersion;
        $pattern->code = 'MICRO_LOSS';
        $pattern->type = 'micro';
        $pattern->finding_code = 'MICRO_LOSS_AGGREGATOR';
        $pattern->severity = 'low';
        $pattern->occurrences = (int) ($pattern->occurrences ?? 0) + $microRows->count();
        $pattern->occurrence_count = $pattern->occurrences;
        $pattern->total_amount = round((float) ($pattern->total_amount ?? 0) + (float) $microRows->sum('amount'), 2);
        $pattern->avg_confidence = round((float) $microRows->avg('confidence_score'), 2);
        $pattern->first_seen_at = $pattern->first_seen_at ?: now();
        $pattern->last_seen_at = now();
        $pattern->sample_finding_id = (int) $microRows->first()->id;
        $pattern->meta = ['segment' => 'micro_loss', 'row_count' => $microRows->count()];
        $pattern->examples_json = $pattern->meta;
        $pattern->save();
    }
}
