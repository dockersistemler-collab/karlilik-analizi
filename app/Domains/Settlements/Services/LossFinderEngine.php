<?php

namespace App\Domains\Settlements\Services;

class LossFinderEngine
{
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
        float $tolerance = 0.01
    ): array {
        $findings = [];

        if ($hasOrder && !$hasPayoutRows) {
            $findings[] = $this->finding(
                'LOSS_MISSING_IN_PAYOUT',
                'Hakedişte Sipariş Satırı Yok',
                'Sipariş için beklenen kalem var ancak payout içinde satır bulunamadı.',
                'high',
                $this->sum($expectedByType),
                'missing_in_payout',
                'MISSING_PAYMENT'
            );
        }

        if (!$hasOrder && $hasPayoutRows) {
            $findings[] = $this->finding(
                'LOSS_MISSING_IN_ORDERS',
                'Sipariş Bulunamadı',
                'Payout satırı mevcut ancak sipariş kaydı bulunamadı.',
                'high',
                $this->sum($actualByType),
                'missing_in_orders',
                'UNKNOWN_DEDUCTION'
            );
        }

        $commissionDiff = round(($actualByType['commission'] ?? 0.0) - ($expectedByType['commission'] ?? 0.0), 2);
        if ($commissionDiff < -$tolerance) {
            $findings[] = $this->finding(
                'LOSS_COMMISSION_HIGH',
                'Yüksek Komisyon Kesintisi',
                'Komisyon kesintisi beklenenden daha yüksek.',
                abs($commissionDiff) >= 10 ? 'high' : 'medium',
                abs($commissionDiff),
                'commission',
                'COMMISSION_DIFF'
            );
        }

        $shippingDiff = round(($actualByType['shipping'] ?? 0.0) - ($expectedByType['shipping'] ?? 0.0), 2);
        $shippingRows = array_values(array_filter($actualRows, fn (array $row): bool => ($row['type'] ?? '') === 'shipping'));
        if ($shippingDiff > $tolerance || count($shippingRows) > 1) {
            $detail = $shippingDiff > $tolerance
                ? 'Kargo tutarı beklenenden yüksek.'
                : 'Kargo satırı birden fazla kez gözüküyor.';
            $findings[] = $this->finding(
                'LOSS_SHIPPING_DUP_OR_HIGH',
                'Kargo Farkı',
                $detail,
                'medium',
                abs($shippingDiff),
                'shipping',
                'SHIPPING_DIFF'
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
                'KDV Uyuşmazlığı',
                'Satırlarda gross/vat/net toplamları tutarsız.',
                'medium',
                round($vatMismatchAmount, 2),
                'vat',
                'VAT_DIFF'
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
                'Bilinmeyen Kesinti',
                'Beklenmeyen kesinti satırları tespit edildi.',
                'medium',
                round($unknownDeduction, 2),
                'deduction',
                'UNKNOWN_DEDUCTION'
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
                'Mikro Kesinti Kümesi',
                "Tekrarlayan mikro kesinti tespit edildi ({$microCount} satır).",
                'low',
                round($microLossTotal, 2),
                'micro',
                'UNKNOWN_DEDUCTION'
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
     * @return array<string,mixed>
     */
    private function finding(
        string $code,
        string $title,
        string $detail,
        string $severity,
        float $amount,
        string $type,
        string $suggestedDisputeType
    ): array {
        return [
            'code' => $code,
            'title' => $title,
            'detail' => $detail,
            'severity' => $severity,
            'amount' => round($amount, 2),
            'type' => $type,
            'suggested_dispute_type' => $suggestedDisputeType,
        ];
    }
}

