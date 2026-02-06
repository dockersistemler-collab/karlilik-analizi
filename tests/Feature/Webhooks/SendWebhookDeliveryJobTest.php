<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\SendWebhookDeliveryJob;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendWebhookDeliveryJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not available in this environment.');
        }

        parent::setUp();
    }

    public function test_job_success_marks_delivery_success(): void
    {
        Http::fake([
            'https://example.com/webhooks' => Http::response('ok', 200),
        ]);

        $user = User::factory()->create(['role' => 'client']);

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

        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'user_id' => $user->id,
            'event' => 'einvoice.created',
            'payload_json' => ['event' => 'einvoice.created', 'id' => '1', 'created_at' => now()->toISOString(), 'data' => []],
            'attempt' => 0,
            'status' => 'pending',
            'request_id' => 'req-1',
            'delivery_uuid' => '00000000-0000-0000-0000-000000000001',
        ]);

        (new SendWebhookDeliveryJob($delivery->id, 0))->handle();

        $delivery->refresh();

        $this->assertSame('success', $delivery->status);
        $this->assertSame(200, $delivery->http_status);
        $this->assertSame(0, $delivery->attempt);
    }

    public function test_job_failure_schedules_retry(): void
    {
        Bus::fake();
        Http::fake([
            'https://example.com/webhooks' => Http::response('bad', 500),
        ]);

        $user = User::factory()->create(['role' => 'client']);

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

        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'user_id' => $user->id,
            'event' => 'einvoice.created',
            'payload_json' => ['event' => 'einvoice.created', 'id' => '1', 'created_at' => now()->toISOString(), 'data' => []],
            'attempt' => 0,
            'status' => 'pending',
            'request_id' => 'req-1',
            'delivery_uuid' => '00000000-0000-0000-0000-000000000001',
        ]);

        (new SendWebhookDeliveryJob($delivery->id, 0))->handle();

        $delivery->refresh();

        $this->assertSame('retrying', $delivery->status);
        $this->assertNotNull($delivery->next_retry_at);
        $this->assertSame(1, $delivery->attempt);
        $this->assertSame(500, $delivery->http_status);

        Bus::assertDispatched(SendWebhookDeliveryJob::class, function (SendWebhookDeliveryJob $job) use ($delivery) {
            return $job->deliveryId === $delivery->id && $job->attempt === 1;
        });
    }

    public function test_signature_headers_are_sent_and_verifiable(): void
    {
        $captured = null;

        Http::fake(function ($request) use (&$captured) {
            $captured = $request;
            return Http::response('ok', 200);
        });

        $user = User::factory()->create(['role' => 'client']);

        $endpoint = WebhookEndpoint::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'url' => 'https://example.com/webhooks',
            'secret' => 'secret',
            'events' => ['einvoice.*'],
            'is_active' => true,
            'headers_json' => ['X-Customer' => 'abc'],
            'timeout_seconds' => 10,
        ]);

        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'user_id' => $user->id,
            'event' => 'einvoice.created',
            'payload_json' => ['event' => 'einvoice.created', 'id' => '1', 'created_at' => now()->toISOString(), 'data' => ['x' => 1]],
            'attempt' => 0,
            'status' => 'pending',
            'request_id' => 'req-1',
            'delivery_uuid' => '00000000-0000-0000-0000-000000000001',
        ]);

        (new SendWebhookDeliveryJob($delivery->id, 0))->handle();

        $this->assertNotNull($captured);

        $sig = $captured->header('X-Webhook-Signature');
        $ts = $captured->header('X-Webhook-Timestamp');
        $this->assertIsString($sig);
        $this->assertIsString($ts);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $sig);
        $this->assertSame('einvoice.created', $captured->header('X-Webhook-Event'));
        $this->assertMatchesRegularExpression('/^[0-9a-f\\-]{36}$/i', (string) $captured->header('X-Webhook-Id'));
        $this->assertSame('abc', $captured->header('X-Customer'));

        $t = (int) $ts;
        $v1 = $sig;

        $expected = hash_hmac('sha256', $t.'.'.$captured->body(), 'secret');
        $this->assertSame($expected, $v1);
        $this->assertSame((string) $t, $ts);
    }
}
