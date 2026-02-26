<?php

namespace App\Domains\Settlements\Services;

class LossFinderEngine
{
    public function __construct(
        private readonly TenantRuleResolver $tenantRuleResolver
    ) {
    }

    /**
     * @param  array<string,float>  $expectedByType
     * @param  array<string,float>  $actualByType
     * @param  array<int,array<string,mixed>>  $actualRows
     * @param  array<int,array<string,mixed>>  $expectedItems
     * @return array<int,array<string,mixed>>
     */
    public function analyze(
        array $expectedByType,
        array $actualByType,
        array $actualRows,
        array $expectedItems,
        bool $hasOrder,
        bool $hasPayoutRows,
        float $tolerance = 0.01,
        ?int $tenantId = null,
        string $marketplace = 'trendyol'
    ): array {
        $findings = [];
        $commissionPctThreshold = $tenantId
            ? $this->tenantRuleResolver->lossThreshold($tenantId, $marketplace, 'commission_pct_threshold', 0.05)
            : 0.05;

        if ($hasOrder && !$hasPayoutRows) {
            $findings[] = $this->finding(
                'LOSS_MISSING_IN_PAYOUT',
                'Hak ediste siparis satiri yok',
                'Siparis icin beklenen kalem var ancak payout icinde satir bulunamadi.',
                'high',
                $this->sum($expectedByType),
                'missing_in_payout',
                'MISSING_PAYMENT',
                ['evidence_count' => 2, 'row_count' => 0, 'pct_diff' => 1]
            );
        }

        if (!$hasOrder && $hasPayoutRows) {
            $findings[] = $this->finding(
                'LOSS_MISSING_IN_ORDERS',
                'Siparis bulunamadi',
                'Payout satiri mevcut ancak siparis kaydi bulunamadi.',
                'high',
                $this->sum($actualByType),
                'missing_in_orders',
                'UNKNOWN_DEDUCTION',
                ['evidence_count' => 2, 'row_count' => count($actualRows), 'pct_diff' => 1]
            );
        }

        $commissionDiff = round(($actualByType['commission'] ?? 0.0) - ($expectedByType['commission'] ?? 0.0), 2);
        $expectedCommission = abs((float) ($expectedByType['commission'] ?? 0));
        $commissionPctDiff = $expectedCommission > 0 ? abs($commissionDiff) / $expectedCommission : 0.0;
        if ($commissionDiff < -$tolerance && $commissionPctDiff >= $commissionPctThreshold) {
            $commissionRows = array_values(array_filter(
                $actualRows,
                fn (array $row): bool => ($row['type'] ?? '') === 'commission'
            ));
            $findings[] = $this->finding(
                'LOSS_COMMISSION_HIGH',
                'Yuksek komisyon kesintisi',
                'Komisyon kesintisi beklenenden daha yuksek.',
                abs($commissionDiff) >= 10 ? 'high' : 'medium',
                abs($commissionDiff),
                'commission',
                'COMMISSION_DIFF',
                [
                    'evidence_count' => 2,
                    'row_count' => count($commissionRows),
                    'pct_diff' => round($commissionPctDiff, 4),
                ]
            );
        }

        $shippingDiff = round(($actualByType['shipping'] ?? 0.0) - ($expectedByType['shipping'] ?? 0.0), 2);
        $shippingRows = array_values(array_filter($actualRows, fn (array $row): bool => ($row['type'] ?? '') === 'shipping'));
        if ($shippingDiff > $tolerance || count($shippingRows) > 1) {
            $detail = $shippingDiff > $tolerance
                ? 'Kargo tutari beklenenden yuksek.'
                : 'Kargo satiri birden fazla kez gozukuyor.';
            $findings[] = $this->finding(
                'LOSS_SHIPPING_DUP_OR_HIGH',
                'Kargo farki',
                $detail,
                'medium',
                abs($shippingDiff),
                'shipping',
                'SHIPPING_DIFF',
                [
                    'evidence_count' => 2,
                    'row_count' => count($shippingRows),
                    'pct_diff' => (float) ($expectedByType['shipping'] ?? 0.0) !== 0.0
                        ? round(abs($shippingDiff) / max(abs((float) $expectedByType['shipping']), 0.01), 4)
                        : 1.0,
                ]
            );
        }

        $vatInconsistent = false;
        $vatMismatchAmount = 0.0;
        foreach ($actualRows as $row) {
            $gross = (float) ($row['gross_amount'] ?? 0.0);
            $vat = (float) ($row['vat_amount'] ?? 0.0);
            $net = (float) ($row['net_amount'] ?? 0.0);
            $delta = round(($gross - $vat) - $net, 2);
            if (abs($delta) > $tolerance) {
                $vatInconsistent = true;
                $vatMismatchAmount += abs($delta);
            }
        }
        if ($vatInconsistent) {
            $findings[] = $this->finding(
                'LOSS_VAT_MISMATCH',
                'KDV uyusmazligi',
                'Satirlarda gross/vat/net toplamlar tutarsiz.',
                'medium',
                round($vatMismatchAmount, 2),
                'vat',
                'VAT_DIFF',
                [
                    'evidence_count' => 1,
                    'row_count' => count($actualRows),
                    'pct_diff' => round($vatMismatchAmount / max(abs($this->sum($actualByType)), 0.01), 4),
                ]
            );
        }

        $unknownDeduction = 0.0;
        foreach ($actualRows as $row) {
            $type = (string) ($row['type'] ?? '');
            if (in_array($type, ['other', 'penalty'], true)) {
                $unknownDeduction += abs((float) ($row['net_amount'] ?? 0.0));
            }
        }
        if ($unknownDeduction > $tolerance) {
            $findings[] = $this->finding(
                'LOSS_UNKNOWN_DEDUCTION',
                'Bilinmeyen kesinti',
                'Beklenmeyen kesinti satirlari tespit edildi.',
                'medium',
                round($unknownDeduction, 2),
                'deduction',
                'UNKNOWN_DEDUCTION',
                [
                    'evidence_count' => 1,
                    'row_count' => count($actualRows),
                    'pct_diff' => round($unknownDeduction / max(abs($this->sum($actualByType)), 0.01), 4),
                ]
            );
        }

        $microLossTotal = 0.0;
        $microCount = 0;
        foreach ($actualRows as $row) {
            $amount = abs((float) ($row['net_amount'] ?? 0.0));
            if ($amount >= 0.01 && $amount <= 2.00) {
                $microLossTotal += $amount;
                $microCount++;
            }
        }
        if ($microCount > 1 && $microLossTotal > $tolerance) {
            $findings[] = $this->finding(
                'MICRO_LOSS_AGGREGATOR',
                'Mikro kesinti kumesi',
                "Tekrarlayan mikro kesinti tespit edildi ({$microCount} satir).",
                'low',
                round($microLossTotal, 2),
                'micro',
                'UNKNOWN_DEDUCTION',
                [
                    'evidence_count' => 1,
                    'row_count' => $microCount,
                    'pct_diff' => 0.02,
                    'micro_segment' => true,
                ]
            );
        }

        return $findings;
    }

    /**
     * @param  array<string,float>  $values
     */
    private function sum(array $values): float
    {
        return round(array_sum($values), 2);
    }

    /**
     * @param  array<string,mixed>  $meta
     * @return array<string,mixed>
     */
    private function finding(
        string $code,
        string $title,
        string $detail,
        string $severity,
        float $amount,
        string $type,
        string $suggestedDisputeType,
        array $meta = []
    ): array {
        return [
            'code' => $code,
            'title' => $title,
            'detail' => $detail,
            'severity' => $severity,
            'amount' => round($amount, 2),
            'type' => $type,
            'suggested_dispute_type' => $suggestedDisputeType,
            'meta' => $meta,
        ];
    }
}
