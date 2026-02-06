<?php

namespace App\Services\Webhooks;

use App\Jobs\SendWebhookDeliveryJob;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class WebhookService
{
    public const MODULE_CODE = 'feature.einvoice_webhooks';

    /**
     * @param array<string,mixed> $payload
     */
    public function dispatchEvent(User $user, string $event, array $payload, ?string $moduleCode = null): void
    {
        $event = trim($event);
        if ($event === '') {
            return;
        }
$moduleCode = $moduleCode ?: self::MODULE_CODE;
        if (!app(EntitlementService::class)->hasModule($user, $moduleCode)) {
            return;
        }
$endpoints = WebhookEndpoint::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($endpoints->isEmpty()) {
            return;
        }

        foreach ($endpoints as $endpoint) {
            $patterns = is_array($endpoint->events) ? $endpoint->events : [];
            if (!$this->eventMatches($event, $patterns)) {
                continue;
            }
[$subjectType, $subjectId] = $this->resolveSubject($payload);
            $dedupeKey = $this->buildDedupeKey($endpoint->id, $event, $payload, $subjectType, $subjectId);

            $existing = WebhookDelivery::query()
                ->where('webhook_endpoint_id', $endpoint->id)
                ->where('dedupe_key', $dedupeKey)
                ->first();

            if ($existing) {
                continue;
            }
$payloadWithEnvelope = [
                'event' => $event,
                'id' => null,
                'created_at' => null,
                'data' => $payload,
            ];

            $payloadLog = $this->buildPayloadForLog($payloadWithEnvelope);

            $delivery = WebhookDelivery::create([
                'webhook_endpoint_id' => $endpoint->id,
                'user_id' => $user->id,
                'delivery_uuid' => Str::uuid()->toString(),
                'event' => $event,
                'payload_json' => [],
                'payload_log_json' => $payloadLog,
                'dedupe_key' => $dedupeKey,
                'attempt' => 0,
                'status' => 'pending',
                'request_id' => Str::uuid()->toString(),
            ]);

            $payloadWithEnvelope['id'] = (string) ($delivery->delivery_uuid ?: $delivery->id);
            $payloadWithEnvelope['created_at'] = $delivery->created_at?->toISOString();
$delivery->payload_json = $payloadWithEnvelope;
            $delivery->payload_log_json = $this->buildPayloadForLog($payloadWithEnvelope);
            $delivery->save();

            SendWebhookDeliveryJob::dispatch($delivery->id, 0);
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function dispatchEventToEndpoint(User $user, WebhookEndpoint $endpoint, string $event, array $payload, ?string $moduleCode = null): void
    {
        $event = trim($event);
        if ($event === '') {
            return;
        }

        if ($endpoint->user_id !== $user->id) {
            return;
        }

        if (!$endpoint->is_active) {
            return;
        }
$moduleCode = $moduleCode ?: self::MODULE_CODE;
        if (!app(EntitlementService::class)->hasModule($user, $moduleCode)) {
            return;
        }
[$subjectType, $subjectId] = $this->resolveSubject($payload);
        $dedupeKey = $this->buildDedupeKey($endpoint->id, $event, $payload, $subjectType, $subjectId);

        $existing = WebhookDelivery::query()
            ->where('webhook_endpoint_id', $endpoint->id)
            ->where('dedupe_key', $dedupeKey)
            ->first();

        if ($existing) {
            return;
        }
$payloadWithEnvelope = [
            'event' => $event,
            'id' => null,
            'created_at' => null,
            'data' => $payload,
        ];

        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'user_id' => $user->id,
            'delivery_uuid' => Str::uuid()->toString(),
            'event' => $event,
            'payload_json' => [],
            'payload_log_json' => $this->buildPayloadForLog($payloadWithEnvelope),
            'dedupe_key' => $dedupeKey,
            'attempt' => 0,
            'status' => 'pending',
            'request_id' => Str::uuid()->toString(),
        ]);

        $payloadWithEnvelope['id'] = (string) ($delivery->delivery_uuid ?: $delivery->id);
        $payloadWithEnvelope['created_at'] = $delivery->created_at?->toISOString();
$delivery->payload_json = $payloadWithEnvelope;
        $delivery->payload_log_json = $this->buildPayloadForLog($payloadWithEnvelope);
        $delivery->save();

        SendWebhookDeliveryJob::dispatch($delivery->id, 0);
    }

    /**
     * @param array<int,string> $patterns
     */
    private function eventMatches(string $event, array $patterns): bool
    {
        $event = trim($event);
        if ($event === '') {
            return false;
        }
$patterns = array_values(array_filter(array_map(fn ($p) => is_string($p) ? trim($p) : '', $patterns), fn ($p) => $p !== ''));
        if (empty($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $event)) {
                return true;
            }
        }

        return false;
    }

    public static function nextRetryAtForAttempt(int $attempt): ?Carbon
    {
        $seconds = match ($attempt) {
            1 => 60,
            2 => 300,
            3 => 900,
            4 => 3600,
            5 => 21600,
            6 => 86400,
            default => null,
        };

        if ($seconds === null) {
            return null;
        }
$jitter = random_int(5, 30);

        return Carbon::now()->addSeconds($seconds + $jitter);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>|null
     */
    private function buildPayloadForLog(array $payload): ?array
    {
        if (!config('webhooks.log_payload', true)) {
            return null;
        }

        if (config('webhooks.log_pii', false)) {
            return $payload;
        }

        return $this->maskPii($payload);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function maskPii(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $val) {
                if (is_string($key)) {
                    $k = strtolower($key);
                    if (str_contains($k, 'email')) {
                        $out[$key] = $this->maskEmail(is_string($val) ? $val : null);
                        continue;
                    }
                    if (str_contains($k, 'phone')) {
                        $out[$key] = $this->maskPhone(is_string($val) ? $val : null);
                        continue;
                    }
                }
$out[$key] = $this->maskPii($val);
            }
            return $out;
        }

        return $value;
    }

    private function maskEmail(?string $email): ?string
    {
        if (!is_string($email) || trim($email) === '') {
            return null;
        }
$email = trim($email);
        if (!str_contains($email, '@')) {
            return '***';
        }
[$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return '***';
        }
$localMasked = mb_substr($local, 0, 1).'***';
        return $localMasked.'@'.$domain;
    }

    private function maskPhone(?string $phone): ?string
    {
        if (!is_string($phone) || trim($phone) === '') {
            return null;
        }
$digits = preg_replace('/\\D+/', '', $phone);
        if (!is_string($digits) || $digits === '') {
            return '***';
        }
$last = strlen($digits) >= 4 ? substr($digits, -4) : $digits;
        return '***'.$last;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{0:string,1:string}
     */
    private function resolveSubject(array $payload): array
    {
        $subjectType = 'unknown';
        $subjectId = '0';

        if (isset($payload['einvoice']) && is_array($payload['einvoice'])) {
            $subjectType = 'einvoice';
            $id = $payload['einvoice']['id'] ?? null;
            $subjectId = is_scalar($id) ? (string) $id : '0';
        }

        if (isset($payload['order']) && is_array($payload['order'])) {
            $subjectType = 'order';
            $id = $payload['order']['id'] ?? null;
            $subjectId = is_scalar($id) ? (string) $id : '0';
        }

        return [$subjectType, $subjectId];
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function buildDedupeKey(int $endpointId, string $event, array $payload, string $subjectType, string $subjectId): string
    {
        if (isset($payload['dedupe_key']) && is_string($payload['dedupe_key']) && trim($payload['dedupe_key']) !== '') {
            $suffix = trim($payload['dedupe_key']);
            return sha1($endpointId.'|'.$event.'|'.$suffix);
        }
$payloadHash = sha1($this->stableJson($payload));
        return sha1($endpointId.'|'.$event.'|'.$subjectType.'|'.$subjectId.'|'.$payloadHash);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function stableJson(array $payload): string
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($encoded) ? $encoded : '';
    }
}
