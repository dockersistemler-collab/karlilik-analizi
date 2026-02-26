<?php

namespace App\Domains\Settlements\Services;

class ConfidenceScoringService
{
    /**
     * @param  array<string,mixed>  $finding
     */
    public function score(array $finding): float
    {
        $code = strtoupper((string) ($finding['code'] ?? ''));
        $meta = is_array($finding['meta'] ?? null) ? $finding['meta'] : [];

        $evidenceCount = (int) ($meta['evidence_count'] ?? 0);
        $rowCount = (int) ($meta['row_count'] ?? 0);
        $pctDiff = abs((float) ($meta['pct_diff'] ?? 0));

        $base = match (true) {
            in_array($code, ['LOSS_MISSING_IN_PAYOUT', 'LOSS_MISSING_IN_ORDERS'], true) => 92,
            $code === 'LOSS_SHIPPING_DUP_OR_HIGH' => 82,
            $code === 'LOSS_COMMISSION_HIGH' => 72,
            $code === 'LOSS_VAT_MISMATCH' => 62,
            default => 45,
        };

        $score = $base;
        if ($evidenceCount >= 3) {
            $score += 5;
        } elseif ($evidenceCount >= 1) {
            $score += 2;
        }

        if ($code === 'LOSS_SHIPPING_DUP_OR_HIGH' && $rowCount >= 2) {
            $score += 8;
        }

        if ($code === 'LOSS_COMMISSION_HIGH' && $pctDiff >= 0.10) {
            $score += 8;
        } elseif ($code === 'LOSS_COMMISSION_HIGH' && $pctDiff >= 0.05) {
            $score += 4;
        }

        if ($code === 'LOSS_VAT_MISMATCH' && $pctDiff >= 0.08) {
            $score += 5;
        }

        return round(max(0, min(100, $score)), 2);
    }
}
