<?php

namespace Tests\Feature\Marketplaces;

use App\Domains\Marketplaces\Connectors\Amazon\AmazonConnector;
use App\Domains\Marketplaces\Connectors\Hepsiburada\HepsiburadaConnector;
use App\Domains\Marketplaces\Connectors\N11\N11Connector;
use App\Domains\Marketplaces\Services\MarketplaceHttpClient;
use App\Domains\Marketplaces\Services\SensitiveValueMasker;
use App\Domains\Marketplaces\Services\SyncLogService;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Models\MarketplaceAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtherMarketplaceConnectorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_hepsiburada_connector_normalizes_orders_returns_and_payouts(): void
    {
        [, $account] = $this->seedAccount('hepsiburada', ['merchant_id' => 'hb-merchant', 'access_token' => 'hb-token']);

        Http::fake([
            '*' => Http::sequence()
                ->push(['items' => [['orderId' => 'HB-ORD-1', 'status' => 'Delivered', 'totalPrice' => 100]]], 200)
                ->push(['items' => [['returnId' => 'HB-RET-1', 'orderId' => 'HB-ORD-1', 'amount' => 30]]], 200)
                ->push(['items' => [['payoutId' => 'HB-PO-1', 'paidAmount' => 70, 'paidDate' => '2026-01-10']]], 200)
                ->push(['items' => [['transactionType' => 'COMMISSION', 'amount' => -10]]], 200),
        ]);

        $connector = new HepsiburadaConnector(
            $account,
            app(MarketplaceHttpClient::class),
            app(SyncLogService::class),
            app(SensitiveValueMasker::class),
            null
        );

        $orders = $connector->fetchOrders(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-02'));
        $returns = $connector->fetchReturns('2026-01-01', '2026-01-02');
        $payouts = $connector->fetchPayouts('2026-01-01', '2026-01-31');
        $tx = $connector->fetchPayoutTransactions('HB-PO-1');

        $this->assertSame('HB-ORD-1', $orders['items'][0]['marketplace_order_id']);
        $this->assertSame('HB-RET-1', $returns['items'][0]['marketplace_return_id']);
        $this->assertSame('HB-PO-1', $payouts['items'][0]['payout_reference']);
        $this->assertSame('COMMISSION', $tx['items'][0]['type']);
    }

    public function test_n11_connector_normalizes_orders_returns_and_payouts(): void
    {
        [, $account] = $this->seedAccount('n11', ['access_token' => 'n11-token']);

        Http::fake([
            '*' => Http::sequence()
                ->push(['data' => [['orderNumber' => 'N11-ORD-1', 'status' => 'Delivered', 'amount' => 95]]], 200)
                ->push(['data' => [['id' => 'N11-RET-1', 'orderNumber' => 'N11-ORD-1', 'amount' => 20]]], 200)
                ->push(['data' => [['id' => 'N11-PO-1', 'paidAmount' => 75, 'paidDate' => '2026-01-10']]], 200)
                ->push(['data' => [['transactionType' => 'SHIPPING', 'amount' => -5]]], 200),
        ]);

        $connector = new N11Connector(
            $account,
            app(MarketplaceHttpClient::class),
            app(SyncLogService::class),
            app(SensitiveValueMasker::class),
            null
        );

        $orders = $connector->fetchOrders(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-02'));
        $returns = $connector->fetchReturns('2026-01-01', '2026-01-02');
        $payouts = $connector->fetchPayouts('2026-01-01', '2026-01-31');
        $tx = $connector->fetchPayoutTransactions('N11-PO-1');

        $this->assertSame('N11-ORD-1', $orders['items'][0]['marketplace_order_id']);
        $this->assertSame('N11-RET-1', $returns['items'][0]['marketplace_return_id']);
        $this->assertSame('N11-PO-1', $payouts['items'][0]['payout_reference']);
        $this->assertSame('SHIPPING', $tx['items'][0]['type']);
    }

    public function test_amazon_connector_normalizes_orders_returns_and_payouts(): void
    {
        [, $account] = $this->seedAccount('amazon', ['access_token' => 'amz-token', 'marketplace_id' => 'A1TEST']);

        Http::fake([
            '*' => Http::sequence()
                ->push(['payload' => ['Orders' => [['AmazonOrderId' => 'AMZ-ORD-1', 'OrderStatus' => 'Shipped', 'OrderTotal' => ['Amount' => 120, 'CurrencyCode' => 'USD']]]]], 200)
                ->push(['payload' => ['returns' => [['AmazonOrderId' => 'AMZ-ORD-1', 'ReturnItemId' => 'AMZ-RET-1', 'RefundAmount' => ['Amount' => 20, 'CurrencyCode' => 'USD']]]]], 200)
                ->push(['payload' => ['FinancialEventGroupList' => [['FinancialEventGroupId' => 'AMZ-PO-1', 'OriginalTotal' => ['Amount' => 100, 'CurrencyCode' => 'USD']]]]], 200)
                ->push(['payload' => ['FinancialEvents' => [['TransactionType' => 'SERVICE_FEE', 'ChargeAmount' => ['Amount' => -4]]]]], 200),
        ]);

        $connector = new AmazonConnector(
            $account,
            app(MarketplaceHttpClient::class),
            app(SyncLogService::class),
            app(SensitiveValueMasker::class),
            null
        );

        $orders = $connector->fetchOrders(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-02'));
        $returns = $connector->fetchReturns('2026-01-01', '2026-01-02');
        $payouts = $connector->fetchPayouts('2026-01-01', '2026-01-31');
        $tx = $connector->fetchPayoutTransactions('AMZ-PO-1');

        $this->assertSame('AMZ-ORD-1', $orders['items'][0]['marketplace_order_id']);
        $this->assertSame('AMZ-RET-1', $returns['items'][0]['marketplace_return_id']);
        $this->assertSame('AMZ-PO-1', $payouts['items'][0]['payout_reference']);
        $this->assertSame('SERVICE_FEE', $tx['items'][0]['type']);
    }

    private function seedAccount(string $marketplaceCode, array $credentials): array
    {
        $tenantOwner = User::factory()->create(['role' => 'client', 'email' => uniqid($marketplaceCode . '_') . '@test.local']);

        DB::table('tenants')->insert([
            'id' => $tenantOwner->id,
            'name' => 'Tenant ' . strtoupper($marketplaceCode),
            'status' => 'active',
            'plan' => 'pro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $integration = MarketplaceIntegration::query()->create([
            'code' => $marketplaceCode,
            'name' => strtoupper($marketplaceCode),
            'is_enabled' => true,
        ]);

        $account = MarketplaceAccount::query()->create([
            'tenant_id' => $tenantOwner->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace' => $marketplaceCode,
            'connector_key' => $marketplaceCode,
            'store_name' => strtoupper($marketplaceCode) . ' Store',
            'credentials' => $credentials,
            'status' => 'active',
        ]);

        return [$tenantOwner, $account];
    }
}
