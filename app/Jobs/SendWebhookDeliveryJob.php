<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\Webhooks\WebhookService;
use App\Services\Webhooks\WebhookUrlGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SendWebhookDeliveryJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'webhooks';

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public int $deliveryId, public int $attempt)
    {
    }

    public function uniqueId(): string
    {
        return "webhook-delivery:{$this->deliveryId}:{$this->attempt}";
    }

    public function handle(): void
    {
        /** @var WebhookDelivery|null $delivery */
        $delivery = WebhookDelivery::query()
            ->with('endpoint')
            ->find($this->deliveryId);

        if (!$delivery || !$delivery->endpoint) {
            return;
        }
$endpoint = $delivery->endpoint;

        if (!$endpoint->is_active) {
            $delivery->status = 'disabled';
            $delivery->next_retry_at = null;
            $delivery->save();
            return;
        }

        if (!in_array($delivery->status, ['pending', 'retrying'], true)) {
            return;
        }

        if ((int) $delivery->attempt !== (int) $this->attempt) {
            return;
        }
$payload = is_array($delivery->payload_json) ? $delivery->payload_json : [];
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($body)) {
            $body = '{}';
        }
$timestamp = time();
        $secret = (string) ($endpoint->secret ?? '');
        $signingInput = $timestamp.'.'.$body;
        $signature = hash_hmac('sha256', $signingInput, $secret);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'PazaryeriEntegrasyon-Webhooks/1.0',
            'X-Webhook-Event' => $delivery->event,
            'X-Webhook-Id' => (string) ($delivery->delivery_uuid ?: $delivery->id),
            'X-Webhook-Timestamp' => (string) $timestamp,
            'X-Webhook-Signature' => $signature,
            'X-Request-Id' => (string) ($delivery->request_id ?: Str::uuid()->toString()),
        ];
        if (!$delivery->request_id) {
            $delivery->request_id = (string) $headers['X-Request-Id'];
            $delivery->save();
        }
$customHeaders = is_array($endpoint->headers_json) ? $endpoint->headers_json : [];
        foreach ($customHeaders as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
$headers[trim($key)] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }
$delivery->signature_timestamp = $timestamp;
        $delivery->signature_v1 = $signature;
        $delivery->request_headers_json = $this->redactHeaders($headers);
        $delivery->request_body = $this->truncateBody($this->encodeRequestBodyForLog($delivery));
        $delivery->save();

        $connectTimeout = (int) config('webhooks.connect_timeout_seconds', 5);
        $connectTimeout = $connectTimeout > 0 && $connectTimeout <= 30 ? $connectTimeout : 5;

        $timeout = (int) config('webhooks.timeout_seconds', 15);
        $timeout = $timeout > 0 && $timeout <= 120 ? $timeout : 15;

        $start = microtime(true);

        try {
            app(WebhookUrlGuard::class)->assertSendable((string) $endpoint->url);

            $response = Http::connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->withoutRedirecting()
                ->withHeaders($headers)
                ->send('POST', $endpoint->url, ['body' => $body]);

            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $delivery->duration_ms = $durationMs;
            $delivery->http_status = $response->status();
            $delivery->response_body = $this->truncateBody($response->body());
            $delivery->response_headers_json = $this->redactHeaders($response->headers());

            if ($response->successful()) {
                $delivery->status = 'success';
                $delivery->next_retry_at = null;
                $delivery->last_error = null;
                $delivery->save();
                return;
            }
$status = $response->status();
            if (in_array($status, [400, 401, 403, 404, 410], true)) {
                $delivery->status = 'failed';
                $delivery->next_retry_at = null;
                $delivery->last_error = "HTTP {$status}";
                $delivery->save();
                $this->maybeTripCircuitBreaker($endpoint->id);
                return;
            }
$this->scheduleRetry($delivery, "HTTP {$status}");
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $delivery->duration_ms = $durationMs;
            $delivery->http_status = null;
            $delivery->response_body = null;
            $delivery->response_headers_json = null;
            $this->scheduleRetry($delivery, $e->getMessage());
        }
    }

    private function scheduleRetry(WebhookDelivery $delivery, string $error): void
    {
        $delivery->last_error = $this->truncateBody($error);

        $nextAttempt = (int) $this->attempt + 1;
        $nextRetryAt = WebhookService::nextRetryAtForAttempt($nextAttempt);
        if (!$nextRetryAt) {
            $delivery->status = 'failed';
            $delivery->next_retry_at = null;
            $delivery->save();
            $this->maybeTripCircuitBreaker((int) $delivery->webhook_endpoint_id);
            return;
        }
$delivery->status = 'retrying';
        $delivery->next_retry_at = $nextRetryAt;
        $delivery->attempt = $nextAttempt;
        $delivery->save();

        $delaySeconds = max(Carbon::now()->diffInSeconds($nextRetryAt, false) * -1, 0);
        self::dispatch($delivery->id, $nextAttempt)->delay($delaySeconds);
    }

    private function truncateBody(?string $body): ?string
    {
        if (!is_string($body) || $body === '') {
            return null;
        }
$max = 10_000;
        if (mb_strlen($body) <= $max) {
            return $body;
        }

        return mb_substr($body, 0, $max);
    }

    /**
     * @param array<string,mixed> $headers
     * @return array<string,mixed>
     */
    private function redactHeaders(array $headers): array
    {
        $sensitive = [
            'authorization',
            'cookie',
            'set-cookie',
            'x-api-key',
            'x-webhook-signature',
        ];

        $out = [];
        foreach ($headers as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                continue;
            }
$k = strtolower(trim($key));
            $isSensitive = in_array($k, $sensitive, true)
                || str_contains($k, 'token')
                || str_contains($k, 'secret')
                || str_contains($k, 'signature');

            if ($isSensitive) {
                $out[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $out[$key] = array_map(fn ($v) => is_string($v) ? $v : (string) $v, $value);
                continue;
            }
$out[$key] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        return $out;
    }

    private function encodeRequestBodyForLog(WebhookDelivery $delivery): string
    {
        $payload = is_array($delivery->payload_log_json) ? $delivery->payload_log_json : null;
        if ($payload === null) {
            return '';
        }
$body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($body) ? $body : '';
    }

    private function maybeTripCircuitBreaker(int $endpointId): void
    {
        $since = Carbon::now()->subHour();

        $attemptCount = WebhookDelivery::query()
            ->where('webhook_endpoint_id', $endpointId)
            ->where('created_at', '>=', $since)
            ->count();

        if ($attemptCount < 25) {
            return;
        }
$failedCount = WebhookDelivery::query()
            ->where('webhook_endpoint_id', $endpointId)
            ->where('created_at', '>=', $since)
            ->where('status', 'failed')
            ->count();

        $failRatio = $attemptCount > 0 ? ($failedCount / $attemptCount) : 0.0;
        if ($failRatio < 0.8) {
            return;
        }

        \App\Models\WebhookEndpoint::query()
            ->whereKey($endpointId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'disabled_at' => Carbon::now(),
                'disabled_reason' => "auto_disabled_fail_ratio:{$failedCount}/{$attemptCount}",
            ]);
    }
}
