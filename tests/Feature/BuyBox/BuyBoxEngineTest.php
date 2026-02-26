<?php

namespace Tests\Feature\BuyBox;

use App\Http\Middleware\EnsureActiveSubscription;
use App\Jobs\CollectBuyBoxSnapshotsJob;
use App\Models\MarketplaceOfferSnapshot;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserModule;
use App\Services\BuyBox\Adapters\MarketplaceAdapterInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuyBoxEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_upserts_snapshot(): void
    {
        $user = $this->bootstrapClientWithBuyboxModule(activeModule: true);

        Product::query()->create([
            'user_id' => $user->id,
            'sku' => 'BUYBOX-SKU-1',
            'name' => 'BuyBox Product',
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        app()->bind('buybox.adapters.trendyol', fn () => new class(2) implements MarketplaceAdapterInterface {
            public function __construct(private readonly int $rank)
            {
            }

            public function fetchOfferSnapshot(int $tenantId, string $sku, Carbon $date): ?array
            {
                return [
                    'sku' => $sku,
                    'is_winning' => $this->rank === 1,
                    'position_rank' => $this->rank,
                    'our_price' => 100,
                    'store_score' => 88.5,
                    'source' => 'api',
                ];
            }

            public function fetchBulkSnapshots(int $tenantId, Carbon $date): iterable
            {
                return [];
            }
        });

        CollectBuyBoxSnapshotsJob::dispatchSync($user->id, now()->toDateString());

        app()->bind('buybox.adapters.trendyol', fn () => new class implements MarketplaceAdapterInterface {
            public function fetchOfferSnapshot(int $tenantId, string $sku, Carbon $date): ?array
            {
                return [
                    'sku' => $sku,
                    'is_winning' => true,
                    'position_rank' => 1,
                    'our_price' => 99.9,
                    'store_score' => 91.0,
                    'source' => 'api',
                ];
            }

            public function fetchBulkSnapshots(int $tenantId, Carbon $date): iterable
            {
                return [];
            }
        });

        CollectBuyBoxSnapshotsJob::dispatchSync($user->id, now()->toDateString());

        $this->assertSame(1, MarketplaceOfferSnapshot::query()->count());
        $row = MarketplaceOfferSnapshot::query()->first();
        $this->assertSame(1, (int) $row->position_rank);
        $this->assertTrue((bool) $row->is_winning);
    }

    public function test_csv_import_creates_snapshot_rows(): void
    {
        $user = $this->bootstrapClientWithBuyboxModule(activeModule: true);
        $this->withoutMiddleware([EnsureActiveSubscription::class]);

        $csv = implode("\n", [
            'date,marketplace,sku,is_winning,position_rank,our_price,competitor_best_price,store_score,stock_available',
            now()->toDateString() . ',trendyol,CSV-SKU-1,1,1,120.50,119.90,90.25,8',
        ]);
        $path = tempnam(sys_get_temp_dir(), 'buybox_csv_');
        file_put_contents($path, $csv);

        $this->actingAs($user)
            ->post(route('portal.buybox.import-csv'), [
                'file' => new \Illuminate\Http\UploadedFile($path, 'buybox.csv', 'text/csv', null, true),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('marketplace_offer_snapshots', [
            'tenant_id' => $user->id,
            'marketplace' => 'trendyol',
            'sku' => 'CSV-SKU-1',
        ]);
    }

    public function test_module_disabled_blocks_route_and_job(): void
    {
        $user = $this->bootstrapClientWithBuyboxModule(activeModule: false);

        Product::query()->create([
            'user_id' => $user->id,
            'sku' => 'BUYBOX-SKU-2',
            'name' => 'BuyBox Product 2',
            'price' => 50,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        app()->bind('buybox.adapters.trendyol', fn () => new class implements MarketplaceAdapterInterface {
            public function fetchOfferSnapshot(int $tenantId, string $sku, Carbon $date): ?array
            {
                return [
                    'sku' => $sku,
                    'is_winning' => true,
                    'position_rank' => 1,
                    'source' => 'api',
                ];
            }

            public function fetchBulkSnapshots(int $tenantId, Carbon $date): iterable
            {
                return [];
            }
        });

        $this->actingAs($user)
            ->get(route('portal.buybox.index'))
            ->assertNotFound();

        CollectBuyBoxSnapshotsJob::dispatchSync($user->id, now()->toDateString());
        $this->assertSame(0, MarketplaceOfferSnapshot::query()->count());
    }

    private function bootstrapClientWithBuyboxModule(bool $activeModule): User
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
            'tenant_id' => null,
        ]);

        DB::table('tenants')->insert([
            'id' => $user->id,
            'name' => 'Tenant '.$user->id,
            'status' => 'active',
            'plan' => 'pro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->forceFill(['tenant_id' => $user->id])->save();

        $module = Module::query()->create([
            'code' => 'buybox_engine',
            'name' => 'BuyBox Engine',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => $activeModule,
            'sort_order' => 1,
        ]);

        $plan = Plan::query()->create([
            'name' => 'BuyBox Plan',
            'slug' => 'buybox-plan-' . ($activeModule ? 'on' : 'off') . '-' . $user->id,
            'price' => 10,
            'billing_period' => 'monthly',
            'features' => ['modules' => ['buybox_engine']],
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 10,
            'billing_period' => 'monthly',
        ]);

        UserModule::query()->create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        return $user;
    }
}

