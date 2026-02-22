<?php

namespace App\Domains\Marketplaces\Mappers;

use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\PayoutTransaction;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use Carbon\Carbon;

class TrendyolMapper
{
    public function mapFinance(
        MarketplaceAccount $account,
        string $from,
        string $to,
        array $settlementRows,
        array $paymentOrderRows
    ): void {
        $integration = MarketplaceIntegration::query()->where('code', 'trendyol')->firstOrFail();

        $paymentsByOrder = [];
        foreach ($paymentOrderRows as $row) {
            $paymentOrderId = (string) ($row['paymentOrderId'] ?? '');
            if ($paymentOrderId === '') {
                continue;
            }

            $paymentsByOrder[$paymentOrderId][] = $row;
        }

        $grouped = [];
        foreach ($settlementRows as $row) {
            $paymentOrderId = (string) ($row['paymentOrderId'] ?? '');
            if ($paymentOrderId === '') {
                $paymentOrderId = 'UNASSIGNED-' . (string) ($row['orderNumber'] ?? $row['shipmentPackageId'] ?? uniqid());
            }

            $grouped[$paymentOrderId][] = $this->normalizeSettlementRow($row);
        }

        foreach ($grouped as $paymentOrderId => $rows) {
            $expectedAmount = array_sum(array_map(fn ($r) => (float) $r['sellerRevenueSigned'], $rows));

            $paymentRows = $paymentsByOrder[$paymentOrderId] ?? [];
            $paidAmount = $this->extractPaidAmount($paymentRows);
            $paidDate = $this->extractPaidDate($paymentRows);

            $status = 'EXPECTED';
            if ($paidAmount !== null) {
                $diff = round($expectedAmount - $paidAmount, 4);
                if (abs($diff) < 0.0001) {
                    $status = 'PAID';
                } elseif (abs($paidAmount) < abs($expectedAmount)) {
                    $status = 'PARTIAL_PAID';
                } else {
                    $status = 'DISCREPANCY';
                }
            }

            $payout = Payout::query()->updateOrCreate(
                [
                    'tenant_id' => $account->tenant_id,
                    'marketplace_account_id' => $account->id,
                    'payout_reference' => (string) $paymentOrderId,
                ],
                [
                    'marketplace_integration_id' => $integration->id,
                    'period_start' => Carbon::parse($from)->toDateString(),
                    'period_end' => Carbon::parse($to)->toDateString(),
                    'expected_date' => $paidDate ? Carbon::parse($paidDate)->toDateString() : Carbon::parse($to)->toDateString(),
                    'expected_amount' => round($expectedAmount, 4),
                    'paid_amount' => $paidAmount !== null ? round($paidAmount, 4) : null,
                    'paid_date' => $paidDate ? Carbon::parse($paidDate)->toDateString() : null,
                    'currency' => 'TRY',
                    'status' => $status,
                    'totals' => [
                        'settlement_rows' => count($rows),
                        'payment_rows' => count($paymentRows),
                    ],
                    'raw_payload' => [
                        'settlements' => $rows,
                        'payment_orders' => $paymentRows,
                        'paid_amount_strategy' => 'credit_or_debt_auto',
                        'todo' => 'Verify paid amount direction for PaymentOrder debt/credit semantics with real production sample.',
                    ],
                ]
            );

            PayoutTransaction::query()->where('payout_id', $payout->id)->delete();

            foreach ($rows as $row) {
                $order = $this->resolveOrderReference($account->tenant_id, $row['orderNumber'] ?? null, $row['shipmentPackageId'] ?? null);

                PayoutTransaction::query()->create([
                    'tenant_id' => $account->tenant_id,
                    'payout_id' => $payout->id,
                    'type' => $row['transactionType'] === 'Return' ? 'RETURN' : 'ORDER',
                    'reference_id' => $order?->id,
                    'amount' => (float) $row['sellerRevenueSigned'],
                    'vat_amount' => 0,
                    'meta' => [
                        'orderNumber' => $row['orderNumber'] ?? null,
                        'shipmentPackageId' => $row['shipmentPackageId'] ?? null,
                        'barcode' => $row['barcode'] ?? null,
                        'paymentOrderId' => $row['paymentOrderId'] ?? null,
                    ],
                    'raw_payload' => $row,
                ]);

                $commissionAmount = (float) ($row['commissionAmount'] ?? 0);
                if ($commissionAmount !== 0.0) {
                    PayoutTransaction::query()->create([
                        'tenant_id' => $account->tenant_id,
                        'payout_id' => $payout->id,
                        'type' => 'COMMISSION',
                        'reference_id' => $order?->id,
                        'amount' => -abs($commissionAmount),
                        'vat_amount' => 0,
                        'meta' => [
                            'commissionRate' => $row['commissionRate'] ?? null,
                            'barcode' => $row['barcode'] ?? null,
                            'paymentOrderId' => $row['paymentOrderId'] ?? null,
                        ],
                        'raw_payload' => $row,
                    ]);
                }
            }
        }
    }

    private function normalizeSettlementRow(array $row): array
    {
        $type = (string) ($row['transactionType'] ?? 'Sale');
        $sellerRevenue = (float) ($row['sellerRevenue'] ?? 0);
        $signedRevenue = $type === 'Return' ? -abs($sellerRevenue) : $sellerRevenue;

        return [
            'id' => $row['id'] ?? null,
            'transactionDate' => $row['transactionDate'] ?? null,
            'transactionType' => $type,
            'orderNumber' => $row['orderNumber'] ?? null,
            'shipmentPackageId' => $row['shipmentPackageId'] ?? null,
            'barcode' => $row['barcode'] ?? null,
            'credit' => (float) ($row['credit'] ?? 0),
            'debt' => (float) ($row['debt'] ?? 0),
            'commissionRate' => $row['commissionRate'] ?? null,
            'commissionAmount' => (float) ($row['commissionAmount'] ?? 0),
            'sellerRevenue' => $sellerRevenue,
            'sellerRevenueSigned' => $signedRevenue,
            'paymentOrderId' => isset($row['paymentOrderId']) ? (string) $row['paymentOrderId'] : null,
            'paymentDate' => $row['paymentDate'] ?? null,
        ];
    }

    private function extractPaidAmount(array $paymentRows): ?float
    {
        if ($paymentRows === []) {
            return null;
        }

        $sum = 0.0;
        foreach ($paymentRows as $row) {
            $credit = (float) ($row['credit'] ?? 0);
            $debt = (float) ($row['debt'] ?? 0);
            $sum += $credit > 0 ? $credit : ($debt > 0 ? $debt : 0.0);
        }

        return round($sum, 4);
    }

    private function extractPaidDate(array $paymentRows): ?string
    {
        foreach ($paymentRows as $row) {
            if (!empty($row['transactionDate'])) {
                return (string) $row['transactionDate'];
            }
        }

        return null;
    }

    private function resolveOrderReference(int $tenantId, ?string $orderNumber, ?string $shipmentPackageId): ?Order
    {
        if ($orderNumber) {
            $order = Order::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($q) use ($orderNumber): void {
                    $q->where('marketplace_order_id', $orderNumber)
                        ->orWhere('order_number', $orderNumber);
                })
                ->first();
            if ($order) {
                return $order;
            }
        }

        if ($shipmentPackageId) {
            return Order::query()
                ->where('tenant_id', $tenantId)
                ->where('marketplace_data', 'like', '%' . $shipmentPackageId . '%')
                ->first();
        }

        return null;
    }
}

