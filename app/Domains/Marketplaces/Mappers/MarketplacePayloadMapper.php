<?php

namespace App\Domains\Marketplaces\Mappers;

use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\OrderItem;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\PayoutTransaction;
use App\Domains\Settlements\Models\ReturnRecord;
use App\Domains\Settlements\Rules\RuleEvaluator;
use App\Jobs\CalculateOrderProfitJob;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\User;
use App\Services\Modules\ModuleGate;

class MarketplacePayloadMapper
{
    public function __construct(
        private readonly RuleEvaluator $ruleEvaluator,
        private readonly TrendyolMapper $trendyolMapper,
        private readonly TrendyolOrderMapper $trendyolOrderMapper,
        private readonly ModuleGate $moduleGate
    )
    {
    }

    public function mapOrders(MarketplaceAccount $account, array $payload): void
    {
        $integration = $this->resolveIntegration($account->marketplace);

        $sourceItems = ($integration?->code === 'trendyol')
            ? $this->trendyolOrderMapper->normalize($payload)
            : (array) ($payload['items'] ?? []);

        foreach ($sourceItems as $item) {
            $marketplaceOrderId = (string) (
                $item['marketplace_order_id']
                ?? $item['orderNumber']
                ?? $item['id']
                ?? uniqid('order_', true)
            );
            $lineItems = $item['items'] ?? $item['lines'] ?? $item['lineItems'] ?? [];

            $order = Order::query()->updateOrCreate(
                [
                    'tenant_id' => $account->tenant_id,
                    'marketplace_integration_id' => $integration?->id,
                    'marketplace_account_id' => $account->id,
                    'marketplace_order_id' => $marketplaceOrderId,
                ],
                [
                    'tenant_id' => $account->tenant_id,
                    'user_id' => $account->tenant_id,
                    'marketplace_integration_id' => $integration?->id,
                    'marketplace_account_id' => $account->id,
                    'order_number' => (string) ($item['order_number'] ?? $item['orderNumber'] ?? $marketplaceOrderId),
                    'status' => (string) ($item['status'] ?? $item['shipmentPackageStatus'] ?? 'NEW'),
                    'currency' => (string) ($item['currency'] ?? 'TRY'),
                    'order_date' => $item['order_date'] ?? $item['orderDate'] ?? $item['packageCreationDate'] ?? now(),
                    'totals' => $item['totals'] ?? null,
                    'raw_payload' => $item,
                    'total_amount' => (float) data_get($item, 'totals.gross', 0),
                    'commission_amount' => (float) collect($lineItems)->sum('commission_amount'),
                    'net_amount' => (float) data_get($item, 'totals.net', 0),
                    'customer_name' => $item['customer_name'] ?? trim((string) (($item['customerFirstName'] ?? '') . ' ' . ($item['customerLastName'] ?? ''))) ?: 'Marketplace Customer',
                ]
            );

            foreach ($lineItems as $row) {
                $computed = $this->ruleEvaluator->computeBreakdown($row);
                $barcode = (string) ($row['barcode'] ?? '');
                $shipmentPackageId = (string) ($row['shipmentPackageId'] ?? '');
                OrderItem::query()->updateOrCreate([
                    'tenant_id' => $account->tenant_id,
                    'order_id' => $order->id,
                    'barcode' => $barcode !== '' ? $barcode : null,
                    'shipment_package_id' => $shipmentPackageId !== '' ? $shipmentPackageId : null,
                ], [
                    'tenant_id' => $account->tenant_id,
                    'order_id' => $order->id,
                    'sku' => (string) ($row['sku'] ?? 'UNKNOWN'),
                    'barcode' => $barcode !== '' ? $barcode : null,
                    'shipment_package_id' => $shipmentPackageId !== '' ? $shipmentPackageId : null,
                    'variant_id' => $row['variant_id'] ?? null,
                    'qty' => (int) ($row['qty'] ?? 1),
                    'sale_price' => (float) ($row['sale_price'] ?? 0),
                    'sale_vat' => (float) ($row['sale_vat'] ?? 0),
                    'cost_price' => (float) ($row['cost_price'] ?? 0),
                    'cost_vat' => (float) ($row['cost_vat'] ?? 0),
                    'commission_amount' => (float) ($row['commission_amount'] ?? 0),
                    'commission_vat' => (float) ($row['commission_vat'] ?? 0),
                    'shipping_amount' => (float) ($row['shipping_amount'] ?? 0),
                    'shipping_vat' => (float) ($row['shipping_vat'] ?? 0),
                    'service_fee_amount' => (float) ($row['service_fee_amount'] ?? 0),
                    'service_fee_vat' => (float) ($row['service_fee_vat'] ?? 0),
                    'calculated' => $computed,
                    'raw_payload' => $row,
                ]);
            }

            if (!$order->wasRecentlyCreated) {
                $this->dispatchProfitCalculationForUpsert($order);
            }
        }
    }

    public function mapReturns(MarketplaceAccount $account, array $payload): void
    {
        foreach (($payload['items'] ?? []) as $item) {
            $order = Order::query()
                ->where('tenant_id', $account->tenant_id)
                ->where('marketplace_order_id', (string) data_get($item, 'marketplace_order_id'))
                ->first();

            if (!$order) {
                continue;
            }

            ReturnRecord::query()->updateOrCreate(
                ['marketplace_return_id' => (string) data_get($item, 'marketplace_return_id')],
                [
                    'tenant_id' => $account->tenant_id,
                    'order_id' => $order->id,
                    'status' => (string) data_get($item, 'status', 'OPEN'),
                    'amounts' => data_get($item, 'amounts', []),
                    'raw_payload' => $item,
                ]
            );
        }
    }

    public function mapPayouts(MarketplaceAccount $account, array $payload): void
    {
        $integration = $this->resolveIntegration($account->marketplace);

        foreach (($payload['items'] ?? []) as $item) {
            Payout::query()->updateOrCreate(
                [
                    'tenant_id' => $account->tenant_id,
                    'marketplace_account_id' => $account->id,
                    'payout_reference' => (string) data_get($item, 'payout_reference'),
                ],
                [
                    'marketplace_integration_id' => $integration?->id,
                    'period_start' => (string) data_get($item, 'period_start', now()->toDateString()),
                    'period_end' => (string) data_get($item, 'period_end', now()->toDateString()),
                    'paid_amount' => data_get($item, 'paid_amount'),
                    'paid_date' => data_get($item, 'paid_date'),
                    'currency' => (string) data_get($item, 'currency', 'TRY'),
                    'status' => (string) data_get($item, 'status', 'EXPECTED'),
                    'raw_payload' => $item,
                ]
            );
        }
    }

    public function mapPayoutTransactions(Payout $payout, array $payload): void
    {
        PayoutTransaction::query()->where('payout_id', $payout->id)->delete();

        foreach (($payload['items'] ?? []) as $row) {
            PayoutTransaction::query()->create([
                'tenant_id' => $payout->tenant_id,
                'payout_id' => $payout->id,
                'type' => (string) data_get($row, 'type', 'ADJUSTMENT'),
                'amount' => (float) data_get($row, 'amount', 0),
                'vat_amount' => (float) data_get($row, 'vat_amount', 0),
                'meta' => data_get($row, 'meta', []),
                'raw_payload' => $row,
            ]);
        }
    }

    public function mapTrendyolFinance(
        MarketplaceAccount $account,
        string $from,
        string $to,
        array $settlementRows,
        array $paymentOrderRows
    ): void {
        $this->trendyolMapper->mapFinance($account, $from, $to, $settlementRows, $paymentOrderRows);
    }

    private function resolveIntegration(string $marketplaceCode): ?MarketplaceIntegration
    {
        return MarketplaceIntegration::query()
            ->where('code', strtolower($marketplaceCode))
            ->first();
    }

    private function dispatchProfitCalculationForUpsert(Order $order): void
    {
        $userId = (int) $order->user_id;
        if ($userId <= 0) {
            return;
        }

        $user = User::query()->find($userId);
        if (!$user) {
            return;
        }

        if (!$this->moduleGate->isEnabledForUser($user, 'profit_engine')) {
            return;
        }

        CalculateOrderProfitJob::dispatch($order->id);
    }
}
