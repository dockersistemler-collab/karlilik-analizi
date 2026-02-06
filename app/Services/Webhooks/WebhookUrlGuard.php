<?php

namespace App\Services\Webhooks;

use Illuminate\Validation\ValidationException;

class WebhookUrlGuard
{
    /**
     * @throws ValidationException
     */
    public function assertSendable(string $url): void
    {
        $url = trim($url);
        if ($url === '') {
            throw ValidationException::withMessages([
                'url' => ['URL zorunludur.'],
            ]);
        }
$parts = parse_url($url);
        if (!is_array($parts)) {
            throw ValidationException::withMessages([
                'url' => ['Geçersiz URL.'],
            ]);
        }
$scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        $allowInsecureHttp = (bool) config('webhooks.allow_insecure_http', false);
        if (app()->environment('production')) {
            if ($scheme !== 'https') {
                $this->reject();
            }
        } else {
            if (!in_array($scheme, ['https', 'http'], true)) {
                throw ValidationException::withMessages([
                    'url' => ['URL şeması geçersiz.'],
                ]);
            }
            if (!$allowInsecureHttp && $scheme !== 'https') {
                $this->reject();
            }
        }

        if ($host === '') {
            throw ValidationException::withMessages([
                'url' => ['URL host zorunludur.'],
            ]);
        }
$ips = $this->resolveHostToIps($host);
        if (empty($ips)) {
            throw ValidationException::withMessages([
                'url' => ['Host çözümlenemedi.'],
            ]);
        }

        foreach ($ips as $ip) {
            if ($this->isBlockedIp($ip)) {
                $this->reject();
            }
        }
    }

    /**
     * @return array<int,string>
     */
    private function resolveHostToIps(string $host): array
    {
        $host = trim($host);
        if ($host === '') {
            return [];
        }

        // IP literals
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }
$ips = [];

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $rec) {
                $ip = $rec['ip'] ?? $rec['ipv6'] ?? null;
                if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ips[] = $ip;
                }
            }
        }

        if (empty($ips)) {
            $a = @gethostbynamel($host);
            if (is_array($a)) {
                foreach ($a as $ip) {
                    if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
                        $ips[] = $ip;
                    }
                }
            }
        }
$ips = array_values(array_unique($ips));

        return $ips;
    }

    private function isBlockedIp(string $ip): bool
    {
        $cidrs = [
            '127.0.0.0/8',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '169.254.0.0/16',
            '::1/128',
            'fc00::/7',
            'fe80::/10',
        ];

        foreach ($cidrs as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, null);
        if (!is_string($subnet) || !is_string($bits) || !ctype_digit($bits)) {
            return false;
        }
$bits = (int) $bits;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        if (strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }
$bytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($bytes > 0) {
            if (substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                return false;
            }
        }

        if ($remainder === 0) {
            return true;
        }
$mask = chr((0xFF << (8 - $remainder)) & 0xFF);
        return (ord($ipBin[$bytes]) & ord($mask)) === (ord($subnetBin[$bytes]) & ord($mask));
    }

    /**
     * @throws ValidationException
     */
    private function reject(): void
    {
        throw ValidationException::withMessages([
            'url' => ['Güvenlik nedeniyle bu URL’e webhook gönderilemez.'],
        ]);
    }
}
