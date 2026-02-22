<?php

namespace App\Domains\Marketplaces\Jobs;

use App\Domains\Marketplaces\Mappers\MarketplacePayloadMapper;
use App\Domains\Marketplaces\Connectors\Trendyol\TrendyolConnector;
use App\Domains\Marketplaces\Services\MarketplaceConnectorRegistry;
use App\Domains\Marketplaces\Services\SyncLogService;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\SyncJob;
use App\Models\MarketplaceAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class SyncMarketplaceAccountJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $accountId,
        public readonly string $from,
        public readonly string $to
    ) {
        $this->onQueue('integrations');
    }

    public function handle(
        MarketplaceConnectorRegistry $registry,
        MarketplacePayloadMapper $mapper,
        SyncLogService $logService
    ): void {
        $account = MarketplaceAccount::query()->findOrFail($this->accountId);

        $syncJob = SyncJob::query()->create([
            'tenant_id' => $account->tenant_id,
            'marketplace_account_id' => $account->id,
            'job_type' => 'ORDERS',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $connector = $registry->resolve($account, $syncJob->id);

        $effectiveTo = Carbon::now();
        $effectiveFrom = $account->last_sync_at
            ? Carbon::parse($account->last_sync_at)
            : Carbon::now()->subDays(7);
        if (!empty($this->from)) {
            $effectiveFrom = Carbon::parse($this->from);
        }
        if (!empty($this->to)) {
            $effectiveTo = Carbon::parse($this->to);
        }
        $effectiveFromStr = $effectiveFrom->toDateTimeString();
        $effectiveToStr = $effectiveTo->toDateTimeString();

        $orders = $connector->fetchOrders($effectiveFrom, $effectiveTo, [
            'orderByField' => 'PackageLastModifiedDate',
            'orderByDirection' => 'ASC',
        ], 0, (int) config('marketplaces.trendyol.order_page_size', 200));
        $logService->info($syncJob->id, 'Orders fetched', ['count' => count($orders['items'] ?? [])]);
        $mapper->mapOrders($account, $orders);

        $returns = $connector->fetchReturns($effectiveFromStr, $effectiveToStr);
        $logService->info($syncJob->id, 'Returns fetched', ['count' => count($returns['items'] ?? [])]);
        $mapper->mapReturns($account, $returns);

        if ($connector instanceof TrendyolConnector) {
            $saleRows = $connector->fetchFinanceSettlements($effectiveFromStr, $effectiveToStr, 'Sale', 0, (int) config('marketplaces.trendyol.finance_page_size', 500));
            $returnRows = $connector->fetchFinanceSettlements($effectiveFromStr, $effectiveToStr, 'Return', 0, (int) config('marketplaces.trendyol.finance_page_size', 500));
            $paymentOrderRows = $connector->fetchFinanceOtherFinancials($effectiveFromStr, $effectiveToStr, 'PaymentOrder', 0, (int) config('marketplaces.trendyol.finance_page_size', 500));
            $mapper->mapTrendyolFinance($account, $effectiveFromStr, $effectiveToStr, array_merge($saleRows, $returnRows), $paymentOrderRows);
            $payouts = ['items' => array_merge($saleRows, $returnRows)];
        } else {
            $payouts = $connector->fetchPayouts($effectiveFromStr, $effectiveToStr);
            $mapper->mapPayouts($account, $payouts);
        }
        $logService->info($syncJob->id, 'Payouts fetched', ['count' => count($payouts['items'] ?? [])]);

        if (!($connector instanceof TrendyolConnector)) {
            $createdPayouts = Payout::query()
                ->withoutGlobalScope('tenant_scope')
                ->where('tenant_id', $account->tenant_id)
                ->where('marketplace_account_id', $account->id)
                ->whereBetween('period_start', [$effectiveFromStr, $effectiveToStr])
                ->get();

            foreach ($createdPayouts as $payout) {
                $tx = $connector->fetchPayoutTransactions((string) ($payout->payout_reference ?: $payout->id));
                $mapper->mapPayoutTransactions($payout, $tx);
            }
        }

        $syncJob->update([
            'status' => 'completed',
            'finished_at' => now(),
            'stats' => [
                'orders' => count($orders['items'] ?? []),
                'returns' => count($returns['items'] ?? []),
                'payouts' => count($payouts['items'] ?? []),
            ],
        ]);

        $account->forceFill(['last_sync_at' => now()])->save();
    }
}
