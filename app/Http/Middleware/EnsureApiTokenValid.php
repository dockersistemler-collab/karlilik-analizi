<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiTokenValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (!$token) {
            $plain = $request->bearerToken();
            if ($plain) {
                $token = PersonalAccessToken::findToken($plain);
            }
        }

        if (!$token) {
            return $next($request);
        }
$expiresAt = $token->expires_at ?? null;
        if ($expiresAt) {
            $expiresAt = $expiresAt instanceof Carbon ? $expiresAt : Carbon::parse((string) $expiresAt);
            if ($expiresAt->isPast()) {
                try {
                    $token->delete();
                } catch (\Throwable $e) {
                    // ignore
                }

                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'TOKEN_EXPIRED',
                ], 401);
            }
        }
$allowlist = $this->decodeAllowlist($token->ip_allowlist_json ?? null);
        if (!empty($allowlist)) {
            $ip = (string) $request->ip();
            $allowed = false;

            foreach ($allowlist as $rule) {
                if ($this->ipMatchesRule($ip, $rule)) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                return response()->json([
                    'message' => 'Forbidden.',
                    'error' => 'IP_NOT_ALLOWED',
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * @return array<int,string>
     */
    private function decodeAllowlist(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        return [];
    }

    private function ipMatchesRule(string $ip, string $rule): bool
    {
        $ip = trim($ip);
        $rule = trim($rule);
        if ($ip === '' || $rule === '') {
            return false;
        }

        if (!str_contains($rule, '/')) {
            return filter_var($ip, FILTER_VALIDATE_IP) && $ip === $rule;
        }
[$subnet, $prefix] = array_pad(explode('/', $rule, 2), 2, null);
        $subnet = trim((string) $subnet);
        $prefix = trim((string) $prefix);

        if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($subnet, FILTER_VALIDATE_IP)) {
            return false;
        }

        if ($prefix === '' || !ctype_digit($prefix)) {
            return false;
        }
$prefixInt = (int) $prefix;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        if (strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }
$maxBits = strlen($ipBin) === 4 ? 32 : 128;
        if ($prefixInt < 0 || $prefixInt > $maxBits) {
            return false;
        }
$bytes = intdiv($prefixInt, 8);
        $bits = $prefixInt % 8;

        if ($bytes > 0) {
            if (substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                return false;
            }
        }

        if ($bits === 0) {
            return true;
        }
$mask = (0xFF << (8 - $bits)) & 0xFF;
        return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
    }
}
