<?php

namespace App\Integrations\Marketplaces;

use App\Models\MarketplaceAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseMarketplaceAdapter implements MarketplaceAdapterInterface
{
    protected function httpClient(MarketplaceAccount $account): PendingRequest
    {
        return Http::retry(3, 500)
            ->timeout(30)
            ->acceptJson()
            ->withHeaders($this->defaultHeaders($account));
    }

    /**
     * @return array<string, string>
     */
    abstract protected function defaultHeaders(MarketplaceAccount $account): array;

    protected function sendRequest(
        MarketplaceAccount $account,
        string $method,
        string $url,
        array $options = []
    ): Response {
        $response = $this->httpClient($account)->send($method, $url, $options);

        if ($response->status() === 429) {
            $this->handleRateLimit($response);
            $response = $this->httpClient($account)->send($method, $url, $options);
        }

        return $response;
    }

    protected function handleRateLimit(Response $response): void
    {
        $retryAfter = (int) ($response->header('Retry-After') ?? 0);
        if ($retryAfter <= 0) {
            $retryAfter = 2;
        }

        sleep(min($retryAfter, 10));
    }

    /**
     * Validate and normalize a custom base URL against an allowlist.
     * Returns empty string when invalid or disallowed.
     *
     * @param array<int, string> $allowedHostSuffixes
     */
    protected function allowlistedBaseUrl(
        MarketplaceAccount $account,
        ?string $url,
        array $allowedHostSuffixes,
        string $context
    ): string {
        $candidate = trim((string) $url);
        if ($candidate === '') {
            return '';
        }

        $parts = parse_url($candidate);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            $this->logBaseUrlRejected($account, $candidate, $context, 'invalid_scheme_or_host');
            return '';
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isPrivateIp($host)) {
                $this->logBaseUrlRejected($account, $candidate, $context, 'private_ip');
                return '';
            }
        }

        $allowed = false;
        foreach ($allowedHostSuffixes as $suffix) {
            $suffix = strtolower(trim($suffix));
            if ($suffix === '') {
                continue;
            }
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $this->logBaseUrlRejected($account, $candidate, $context, 'host_not_allowlisted');
            return '';
        }

        return rtrim($candidate, '/');
    }

    private function logBaseUrlRejected(
        MarketplaceAccount $account,
        string $url,
        string $context,
        string $reason
    ): void {
        Log::warning('Marketplace base_url rejected', [
            'context' => $context,
            'account_id' => $account->id,
            'tenant_id' => $account->tenant_id,
            'marketplace' => $account->marketplace,
            'base_url' => $url,
            'reason' => $reason,
        ]);
    }

    private function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long === false) {
                return true;
            }

            $ranges = [
                ['10.0.0.0', '10.255.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.168.0.0', '192.168.255.255'],
                ['127.0.0.0', '127.255.255.255'],
                ['169.254.0.0', '169.254.255.255'],
                ['0.0.0.0', '0.255.255.255'],
            ];

            foreach ($ranges as [$start, $end]) {
                $startLong = ip2long($start);
                $endLong = ip2long($end);
                if ($startLong !== false && $endLong !== false && $long >= $startLong && $long <= $endLong) {
                    return true;
                }
            }

            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $normalized = strtolower($ip);
            return $normalized === '::1'
                || $normalized === '::'
                || str_starts_with($normalized, 'fe80:')
                || str_starts_with($normalized, 'fc')
                || str_starts_with($normalized, 'fd');
        }

        return true;
    }
}
