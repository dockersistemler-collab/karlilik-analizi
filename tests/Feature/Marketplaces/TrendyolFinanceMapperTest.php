<?php

namespace Tests\Feature\Marketplaces;

use App\Domains\Marketplaces\Mappers\TrendyolMapper;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\PayoutTransaction;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrendyolFinanceMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_settlements_and_payment_orders_create_payout_and_transactions(): void
    {
        [$tenantOwner, $account] = $this->seedTenantAndAccount();

        $mapper = app(TrendyolMapper::class);
        $mapper->mapFinance(
            $account,
            '2026-02-01',
            '2026-02-10',
            [
                [
                    'transactionType' => 'Sale',
                    'orderNumber' => 'TY-ORDER-1',
                    'shipmentPackageId' => 'PKG-1',
                    'barcode' => 'BAR-1',
                    'sellerRevenue' => 120.00,
                    'commissionRate' => 12,
                    'commissionAmount' => 14.40,
                    'paymentOrderId' => 'PO-100',
                    'paymentDate' => '2026-02-11T09:00:00+03:00',
                ],
                [
                    'transactionType' => 'Return',
                    'orderNumber' => 'TY-ORDER-2',
                    'shipmentPackageId' => 'PKG-2',
                    'barcode' => 'BAR-2',
                    'sellerRevenue' => 20.00,
                    'commissionRate' => 12,
                    'commissionAmount' => 2.40,
                    'paymentOrderId' => 'PO-100',
                ],
            ],
            [
                [
                    'transactionType' => 'PaymentOrder',
                    'paymentOrderId' => 'PO-100',
                    'credit' => 100.00,
                    'debt' => 0.00,
                    'transactionDate' => '2026-02-11T12:00:00+03:00',
                ],
            ]
        );

        $payout = Payout::query()->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantOwner->id)
            ->where('payout_reference', 'PO-100')
            ->first();

        $this->assertNotNull($payout);
        $this->assertEquals(100.00, (float) $payout->expected_amount);
        $this->assertEquals(100.00, (float) $payout->paid_amount);
        $this->assertEquals('PAID', $payout->status);

        $txCount = PayoutTransaction::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('payout_id', $payout->id)
            ->count();

        $this->assertEquals(4, $txCount);
    }

    public function test_payment_order_id_is_used_as_payout_reference(): void
    {
        [$tenantOwner, $account] = $this->seedTenantAndAccount();

        $mapper = app(TrendyolMapper::class);
        $mapper->mapFinance(
            $account,
            '2026-02-01',
            '2026-02-10',
            [
                ['transactionType' => 'Sale', 'orderNumber' => 'A', 'sellerRevenue' => 50, 'paymentOrderId' => 'PO-1'],
                ['transactionType' => 'Sale', 'orderNumber' => 'B', 'sellerRevenue' => 75, 'paymentOrderId' => 'PO-2'],
            ],
            [
                ['transactionType' => 'PaymentOrder', 'paymentOrderId' => 'PO-1', 'credit' => 50],
                ['transactionType' => 'PaymentOrder', 'paymentOrderId' => 'PO-2', 'credit' => 75],
            ]
        );

        $references = Payout::query()->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantOwner->id)
            ->orderBy('payout_reference')
            ->pluck('payout_reference')
            ->values()
            ->all();

        $this->assertSame(['PO-1', 'PO-2'], $references);
    }

    private function seedTenantAndAccount(): array
    {
        $tenantOwner = User::factory()->create(['role' => 'client', 'email' => uniqid('owner_') . '@test.local']);
        DB::table('tenants')->insert([
            'id' => $tenantOwner->id,
            'name' => 'Tenant Mapper',
            'status' => 'active',
            'plan' => 'pro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $integration = MarketplaceIntegration::query()->create([
            'code' => 'trendyol',
            'name' => 'Trendyol',
            'is_enabled' => true,
        ]);

        $account = MarketplaceAccount::query()->create([
            'tenant_id' => $tenantOwner->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace' => 'trendyol',
            'connector_key' => 'trendyol',
            'store_name' => 'TY Store',
            'credentials' => [
                'api_key' => 'k',
                'api_secret' => 's',
                'seller_id' => '123',
                'store_front_code' => 'storefront',
            ],
            'status' => 'active',
        ]);

        Order::query()->create([
            'tenant_id' => $tenantOwner->id,
            'user_id' => $tenantOwner->id,
            'marketplace_integration_id' => $integration->id,
            'marketplace_account_id' => $account->id,
            'marketplace_order_id' => 'TY-ORDER-1',
            'order_number' => 'TY-ORDER-1',
            'status' => 'DELIVERED',
            'total_amount' => 120,
            'currency' => 'TRY',
            'customer_name' => 'A',
            'order_date' => now(),
        ]);

        return [$tenantOwner, $account];
    }
}

