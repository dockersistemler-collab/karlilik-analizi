<?php

namespace App\Http\Middleware;

use App\Models\ApiAuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiAuditLogger
{
    private const META_QUERY_WHITELIST = [
        'status',
        'type',
        'marketplace',
        'updated_since',
        'page',
        'per_page',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        $user = $request->user();
        $token = $user?->currentAccessToken();
$tokenId = $token?->id;
$requestId = (string) $request->header('X-Request-Id', '');
        if ($requestId === '') {
            $requestId = (string) Str::uuid();
        }
$request->attributes->set('api_request_id', $requestId);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        try {
            $meta = $this->buildMeta($request);

            ApiAuditLog::create([
                'user_id' => $user?->id, 'token_id' => $tokenId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => strtoupper((string) $request->method()),
                'path' => $this->truncate((string) $request->path(), 255),
                'status_code' => (int) $response->getStatusCode(),
                'duration_ms' => $durationMs,
                'request_id' => $this->truncate($requestId, 64),
                'meta' => $meta,
                'created_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('api_audit_log_failed', [
                'message' => $e->getMessage(),
            ]);
        }
$response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function buildMeta(Request $request): ?array
    {
        $query = [];
        foreach (self::META_QUERY_WHITELIST as $key) {
            if ($request->query->has($key)) {
                $query[$key] = $request->query($key);
            }
        }
$einvoice = $request->route('einvoice');
        $einvoiceId = null;
        if (is_object($einvoice) && isset($einvoice->id)) {
            $einvoiceId = $einvoice->id;
        } elseif (is_scalar($einvoice)) {
            $einvoiceId = $einvoice;
        }
$meta = [];
        if (!empty($query)) {
            $meta['query'] = $query;
        }
        if ($einvoiceId !== null) {
            $meta['einvoice_id'] = $einvoiceId;
        }

        return empty($meta) ? null : $meta;
    }

    private function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }
        return mb_substr($value, 0, $max);
    }
}
