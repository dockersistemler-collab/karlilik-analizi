<?php

namespace App\Domains\Settlements\Support;

use Illuminate\Support\Facades\Cache;

class SettlementExportStateStore
{
    private const TTL_SECONDS = 86400;

    public function putQueued(string $token, int $tenantId, int $payoutId): void
    {
        $this->put($token, [
            'token' => $token,
            'tenant_id' => $tenantId,
            'payout_id' => $payoutId,
            'status' => 'queued',
            'error' => null,
            'file_path' => null,
            'filename' => null,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function putProcessing(string $token, int $tenantId, int $payoutId): void
    {
        $state = $this->get($token) ?? [];

        $this->put($token, array_merge($state, [
            'token' => $token,
            'tenant_id' => $tenantId,
            'payout_id' => $payoutId,
            'status' => 'processing',
            'error' => null,
            'updated_at' => now()->toIso8601String(),
        ]));
    }

    public function putReady(string $token, int $tenantId, int $payoutId, string $filePath, string $filename): void
    {
        $this->put($token, [
            'token' => $token,
            'tenant_id' => $tenantId,
            'payout_id' => $payoutId,
            'status' => 'ready',
            'error' => null,
            'file_path' => $filePath,
            'filename' => $filename,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function putFailed(string $token, int $tenantId, int $payoutId, string $error): void
    {
        $this->put($token, [
            'token' => $token,
            'tenant_id' => $tenantId,
            'payout_id' => $payoutId,
            'status' => 'failed',
            'error' => $error,
            'file_path' => null,
            'filename' => null,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function get(string $token): ?array
    {
        $state = Cache::get($this->key($token));

        return is_array($state) ? $state : null;
    }

    private function put(string $token, array $state): void
    {
        Cache::put($this->key($token), $state, now()->addSeconds(self::TTL_SECONDS));
    }

    private function key(string $token): string
    {
        return "settlements:export:{$token}";
    }
}

