<?php

namespace App\Domains\Settlements\Services;

use App\Domains\Settlements\Models\Dispute;

class DisputeService
{
    /**
     * @param  array<int,array<string,mixed>>  $findings
     * @return array<int,Dispute>
     */
    public function createFromFindings(
        int $tenantId,
        int $payoutId,
        ?int $orderId,
        array $findings,
        ?int $actorId = null
    ): array {
        $created = [];

        foreach ($findings as $finding) {
            $type = (string) ($finding['suggested_dispute_type'] ?? 'UNKNOWN_DEDUCTION');
            if (!in_array($type, ['MISSING_PAYMENT', 'COMMISSION_DIFF', 'VAT_DIFF', 'SHIPPING_DIFF', 'UNKNOWN_DEDUCTION'], true)) {
                $type = 'UNKNOWN_DEDUCTION';
            }

            $dispute = Dispute::query()->create([
                'tenant_id' => $tenantId,
                'payout_id' => $payoutId,
                'order_id' => $orderId,
                'dispute_type' => $type,
                'status' => 'OPEN',
                'amount' => round(abs((float) ($finding['amount'] ?? 0)), 2),
                'notes' => (string) ($finding['detail'] ?? ''),
                'evidence_json' => $finding,
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'expected_amount' => 0,
                'actual_amount' => 0,
                'diff_amount' => round(abs((float) ($finding['amount'] ?? 0)), 2),
            ]);

            $created[] = $dispute;
        }

        return $created;
    }

    public function updateStatus(Dispute $dispute, string $status, ?int $actorId = null, ?string $notes = null): Dispute
    {
        $status = strtolower($status);
        if (!in_array($status, ['open', 'in_review', 'resolved', 'rejected'], true)) {
            $status = 'in_review';
        }

        $dispute->status = $status;
        $dispute->updated_by = $actorId;
        if ($notes !== null && trim($notes) !== '') {
            $dispute->notes = trim($notes);
        }
        $dispute->save();

        return $dispute;
    }
}
