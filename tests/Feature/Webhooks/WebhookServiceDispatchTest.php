<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\SendWebhookDeliveryJob;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\Webhooks\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class WebhookServiceDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not available in this environment.');
        }

        parent::setUp();
    }

    private function makeSubscribedUserWithModules(array $modules): User
    {
        $user = User::factory()->create(['role' => 'client']);

        $plan = Plan::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => null,
            'price' => 1,
            'yearly_price' => 10,
            'billing_period' => 'monthly',
            'max_products' => 0,
            'max_marketplaces' => 0,
            'max_orders_per_month' => 0,
            'max_tickets_per_month' => 0,
            'api_access' => false,
            'advanced_reports' => false,
            'priority_support' => false,
            'custom_integrations' => false,
            'features' => ['modules' => $modules],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addMonth(),
            'cancelled_at' => null,
            'amount' => 1,
            'billing_period' => 'monthly',
            'auto_renew' => true,
            'current_products_count' => 0,
            'current_marketplaces_count' => 0,
            'current_month_orders_count' => 0,
            'usage_reset_at' => Carbon::now()->addMonth(),
        ]);

        return $user;
    }

    public function test_event_match_creates_delivery_and_queues_job(): void
    {
        Bus::fake();

        Module::create([
            'code' => 'feature.einvoice_webhooks',
            'name' => 'E-Fatura WebhooklarÄ±',
            'type' => 'feature',
            'billing_type' => 'recurring',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = $this->makeSubscribedUserWithModules(['feature.einvoice_webhooks']);

        $endpoint = WebhookEndpoint::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'url' => 'https://example.com/webhooks',
            'secret' => 'secret',
            'events' => ['einvoice.*'],
            'is_active' => true,
            'headers_json' => null,
            'timeout_seconds' => 10,
        ]);

        app(WebhookService::class)->dispatchEvent($user, 'einvoice.created', [
            'einvoice' => ['id' => 1],
            'user' => ['id' => $user->id],
        ]);

        $this->assertDatabaseCount('webhook_deliveries', 1);

        /** @var WebhookDelivery $delivery */
        $delivery = WebhookDelivery::query()->firstOrFail();

        $this->assertSame($endpoint->id, $delivery->webhook_endpoint_id);
        $this->assertSame('einvoice.created', $delivery->event);
        $this->assertSame('pending', $delivery->status);
        $this->assertSame(0, (int) $delivery->attempt);

        $payload = $delivery->payload_json;
        $this->assertIsArray($payload);
        $this->assertSame('einvoice.created', $payload['event'] ?? null);
        $this->assertSame((string) $delivery->id, $payload['id'] ?? null);

        Bus::assertDispatched(SendWebhookDeliveryJob::class, function (SendWebhookDeliveryJob $job) use ($delivery) {
            return $job->deliveryId === $delivery->id && $job->attempt === 0;
        });
    }

    public function test_non_matching_event_does_not_create_delivery(): void
    {
        Bus::fake();

        $user = $this->makeSubscribedUserWithModules(['feature.einvoice_webhooks']);

        WebhookEndpoint::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'url' => 'https://example.com/webhooks',
            'secret' => 'secret',
            'events' => ['einvoice.issued'],
            'is_active' => true,
            'headers_json' => null,
            'timeout_seconds' => 10,
        ]);

        app(WebhookService::class)->dispatchEvent($user, 'einvoice.created', [
            'einvoice' => ['id' => 1],
            'user' => ['id' => $user->id],
        ]);

        $this->assertDatabaseCount('webhook_deliveries', 0);
        Bus::assertNotDispatched(SendWebhookDeliveryJob::class);
    }

    public function test_no_entitlement_does_not_create_delivery(): void
    {
        Bus::fake();

        $user = $this->makeSubscribedUserWithModules([]);

        WebhookEndpoint::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'url' => 'https://example.com/webhooks',
            'secret' => 'secret',
            'events' => ['einvoice.*'],
            'is_active' => true,
            'headers_json' => null,
            'timeout_seconds' => 10,
        ]);

        app(WebhookService::class)->dispatchEvent($user, 'einvoice.created', [
            'einvoice' => ['id' => 1],
            'user' => ['id' => $user->id],
        ]);

        $this->assertDatabaseCount('webhook_deliveries', 0);
        Bus::assertNotDispatched(SendWebhookDeliveryJob::class);
    }
}
