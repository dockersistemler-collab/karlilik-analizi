<?php

namespace Tests\Feature\Marketplaces;

use App\Domains\Marketplaces\Connectors\Trendyol\TrendyolConnector;
use App\Domains\Marketplaces\Mappers\MarketplacePayloadMapper;
use App\Domains\Marketplaces\Services\MarketplaceHttpClient;
use App\Domains\Marketplaces\Services\SensitiveValueMasker;
use App\Domains\Marketplaces\Services\SyncLogService;
use App\Domains\Settlements\Models\MarketplaceIntegration;
use App\Domains\Settlements\Models\OrderItem;
use App\Models\MarketplaceAccount;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class TrendyolOrderSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_orders_applies_chunking_pagination_and_size_clamp(): void
    {
        [, $account] = $this->seedTenantAndAccount();

        $requests = [];
        Http::fake(function ($request) use (&$requests) {
            $requests[] = [
                'url' => $request->url(),
                'headers' => $request->headers(),
            ];

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) ($query['page'] ?? 0);

            return Http::response([
                'content' => [[
                    'orderNumber' => 'TY-' . ($query['startDate'] ?? 'S') . '-' . $page,
                    'shipmentPackageId' => 'PKG-' . $page,
                    'status' => 'Created',
                    'lines' => [],
                ]],
                'totalPages' => 2,
                'totalElements' => 2,
            ], 200);
        });

        $connector = new TrendyolConnector(
            $account,
            app(MarketplaceHttpClient::class),
            app(SyncLogService::class),
            app(SensitiveValueMasker::class),
            null
        );

        $result = $connector->fetchOrders(
            Carbon::parse('2026-01-01 00:00:00'),
            Carbon::parse('2026-01-31 23:59:59'),
            [],
            0,
            999
        );

        $this->assertCount(6, $requests);
        $this->assertCount(6, $result['items']);

        foreach ($requests as $request) {
            parse_str((string) parse_url($request['url'], PHP_URL_QUERY), $query);
            $this->assertSame('200', (string) ($query['size'] ?? null));
            $this->assertSame('PackageLastModifiedDate', (string) ($query['orderByField'] ?? null));
            $this->assertSame('ASC', (string) ($query['orderByDirection'] ?? null));
            $this->assertNotEmpty($query['startDate'] ?? null);
            $this->assertNotEmpty($query['endDate'] ?? null);
            $this->assertArrayHasKey('storefrontcode', array_change_key_case($request['headers'], CASE_LOWER));
        }
    }

    public function test_fetch_orders_throws_when_shipment_package_ids_exceeds_limit(): void
    {
        [, $account] = $this->seedTenantAndAccount();

        $connector = new TrendyolConnector(
            $account,
            app(MarketplaceHttpClient::class),
            app(SyncLogService::class),
            app(SensitiveValueMasker::class),
            null
        );

        $this->expectException(InvalidArgumentException::class);
        $connector->fetchOrders(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-02'),
            ['shipmentPackageIds' => range(1, 51)],
            0,
            200
        );
    }

    public function test_mapper_upserts_orders_and_order_items_from_shipment_packages_payload(): void
    {
        [$tenant, $account] = $this->seedTenantAndAccount();

        $payload = [
            'shipmentPackages' => [
                [
                    'orderNumber' => 'ORD-1',
                    'shipmentPackageId' => 1001,
                    'orderDate' => '2026-02-21T10:00:00+03:00',
                    'packageStatus' => 'Delivered',
                    'currencyCode' => 'TRY',
                    'totalPrice' => 200,
                    'totalDiscount' => 10,
                    'cargoProviderName' => 'Trendyol Express',
                    'trackingNumber' => 'TRK-1',
                    'lines' => [
                        ['barcode' => 'B-1', 'merchantSku' => 'SKU-1', 'quantity' => 1, 'price' => 100, 'vatAmount' => 18],
                        ['barcode' => 'B-2', 'merchantSku' => 'SKU-2', 'quantity' => 1, 'price' => 100, 'vatAmount' => 18],
                    ],
                ],
                [
                    'orderNumber' => 'ORD-2',
                    'shipmentPackageId' => 1002,
                    'createdDate' => '2026-02-21T11:00:00+03:00',
                    'status' => 'Created',
                    'currencyCode' => 'TRY',
                    'totalPrice' => 150,
                    'lines' => [
                        ['barcode' => 'B-3', 'merchantSku' => 'SKU-3', 'quantity' => 1, 'price' => 75, 'vatAmount' => 13.5],
                        ['barcode' => 'B-4', 'merchantSku' => 'SKU-4', 'quantity' => 1, 'price' => 75, 'vatAmount' => 13.5],
                    ],
                ],
            ],
        ];

        app(MarketplacePayloadMapper::class)->mapOrders($account, $payload);

        $this->assertSame(2, Order::query()->withoutGlobalScope('tenant_scope')->where('tenant_id', $tenant->id)->count());
        $this->assertSame(4, OrderItem::query()->withoutGlobalScope('tenant_scope')->where('tenant_id', $tenant->id)->count());

        app(MarketplacePayloadMapper::class)->mapOrders($account, $payload);
        $this->assertSame(2, Order::query()->withoutGlobalScope('tenant_scope')->where('tenant_id', $tenant->id)->count());
        $this->assertSame(4, OrderItem::query()->withoutGlobalScope('tenant_scope')->where('tenant_id', $tenant->id)->count());
    }

    private function seedTenantAndAccount(): array
    {
        $tenantOwner = User::factory()->create(['role' => 'client', 'email' => uniqid('owner_') . '@test.local']);

        DB::table('tenants')->insert([
            'id' => $tenantOwner->id,
            'name' => 'Tenant Trendyol Orders',
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
                'api_key' => 'key',
                'api_secret' => 'secret',
                'seller_id' => '123',
                'store_front_code' => 'storefront',
            ],
            'status' => 'active',
        ]);

        return [$tenantOwner, $account];
    }
}
