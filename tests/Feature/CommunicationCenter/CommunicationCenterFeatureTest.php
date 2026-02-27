<?php

namespace Tests\Feature\CommunicationCenter;

use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use App\Models\Marketplace;
use App\Models\MarketplaceStore;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserModule;
use App\Http\Controllers\Admin\CommunicationCenterController;
use App\Services\Marketplaces\Contracts\MarketplaceClientInterface;
use App\Services\Marketplaces\MarketplaceClientResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class CommunicationCenterFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'module.enabled:customer_communication_center'])
            ->group(function () {
                Route::get('/communication-center', [CommunicationCenterController::class, 'index']);
                Route::get('/communication-center/questions', [CommunicationCenterController::class, 'list'])
                    ->defaults('channel', 'question');
                Route::post('/communication-center/thread/{thread}/reply', [CommunicationCenterController::class, 'reply']);
            });
    }

    public function test_module_inactive_blocks_communication_center_route(): void
    {
        $user = $this->createClientWithPlan(['customer_communication_center']);

        Module::query()->updateOrCreate(['code' => 'customer_communication_center'], [
            'code' => 'customer_communication_center',
            'name' => 'Müşteri İletişim Merkezi',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->get('/communication-center')
            ->assertStatus(404);
    }

    public function test_user_can_only_see_own_store_threads(): void
    {
        Module::query()->updateOrCreate(['code' => 'customer_communication_center'], [
            'code' => 'customer_communication_center',
            'name' => 'Müşteri İletişim Merkezi',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $userA = $this->createClientWithPlan(['customer_communication_center']);
        $userB = $this->createClientWithPlan(['customer_communication_center']);
        $marketplace = Marketplace::query()->create(['name' => 'Trendyol', 'code' => 'trendyol', 'is_active' => true]);

        $storeA = MarketplaceStore::query()->create([
            'user_id' => $userA->id,
            'marketplace_id' => $marketplace->id,
            'store_name' => 'A Store',
            'is_active' => true,
        ]);
        $storeB = MarketplaceStore::query()->create([
            'user_id' => $userB->id,
            'marketplace_id' => $marketplace->id,
            'store_name' => 'B Store',
            'is_active' => true,
        ]);

        CommunicationThread::query()->create([
            'marketplace_id' => $marketplace->id,
            'marketplace_store_id' => $storeA->id,
            'channel' => 'question',
            'external_thread_id' => 'a1',
            'customer_name' => 'Alice',
            'product_name' => 'Ürün A',
            'status' => 'open',
        ]);
        CommunicationThread::query()->create([
            'marketplace_id' => $marketplace->id,
            'marketplace_store_id' => $storeB->id,
            'channel' => 'question',
            'external_thread_id' => 'b1',
            'customer_name' => 'Bob',
            'product_name' => 'Ürün B',
            'status' => 'open',
        ]);

        $this->actingAs($userA)
            ->get('/communication-center/questions')
            ->assertOk()
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }

    public function test_reply_creates_outbound_message_and_calls_stub_client(): void
    {
        Module::query()->updateOrCreate(['code' => 'customer_communication_center'], [
            'code' => 'customer_communication_center',
            'name' => 'Müşteri İletişim Merkezi',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = $this->createClientWithPlan(['customer_communication_center']);
        $marketplace = Marketplace::query()->create(['name' => 'Trendyol', 'code' => 'trendyol', 'is_active' => true]);
        $store = MarketplaceStore::query()->create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'store_name' => 'Test Store',
            'is_active' => true,
        ]);
        $thread = CommunicationThread::query()->create([
            'marketplace_id' => $marketplace->id,
            'marketplace_store_id' => $store->id,
            'channel' => 'question',
            'external_thread_id' => 'thr-1',
            'customer_name' => 'Alice',
            'status' => 'open',
        ]);

        $client = Mockery::mock(MarketplaceClientInterface::class);
        $client->shouldReceive('sendReply')
            ->once()
            ->andReturn(['ok' => true]);

        $resolver = Mockery::mock(MarketplaceClientResolver::class);
        $resolver->shouldReceive('resolve')->once()->andReturn($client);
        $this->app->instance(MarketplaceClientResolver::class, $resolver);

        $this->actingAs($user)
            ->post('/communication-center/thread/' . $thread->id . '/reply', [
                'body' => 'Merhaba, stok bilgisi güncellendi.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('communication_messages', [
            'thread_id' => $thread->id,
            'direction' => 'outbound',
            'body' => 'Merhaba, stok bilgisi güncellendi.',
            'sent_by_user_id' => $user->id,
        ]);

        $this->assertSame(1, CommunicationMessage::query()->where('thread_id', $thread->id)->count());
    }

    private function createClientWithPlan(array $modules): User
    {
        $user = User::factory()->create(['role' => 'client']);
        $plan = Plan::query()->create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-' . uniqid(),
            'price' => 100,
            'billing_period' => 'monthly',
            'features' => ['modules' => $modules],
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'amount' => 100,
            'billing_period' => 'monthly',
        ]);

        foreach ($modules as $moduleCode) {
            $module = Module::query()->firstOrCreate(
                ['code' => $moduleCode],
                [
                    'name' => $moduleCode,
                    'type' => 'feature',
                    'billing_type' => 'recurring',
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            UserModule::query()->updateOrCreate(
                ['user_id' => $user->id, 'module_id' => $module->id],
                ['status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]
            );
        }

        return $user;
    }
}
