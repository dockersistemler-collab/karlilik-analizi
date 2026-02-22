<?php

namespace App\Domains\Marketplaces\Services;

class SensitiveValueMasker
{
    public function maskArray(array $input): array
    {
        $sensitiveKeys = [
            'authorization',
            'api_key',
            'api_secret',
            'access_token',
            'refresh_token',
            'token',
            'password',
            'secret',
        ];

        foreach ($input as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if (is_array($value)) {
                $input[$key] = $this->maskArray($value);
                continue;
            }

            if (in_array($normalizedKey, $sensitiveKeys, true)) {
                $input[$key] = $this->maskValue((string) $value);
            }
        }

        return $input;
    }

    private function maskValue(string $value): string
    {
        if (strlen($value) <= 6) {
            return '***';
        }

        return substr($value, 0, 3) . str_repeat('*', max(strlen($value) - 6, 3)) . substr($value, -3);
    }
}

