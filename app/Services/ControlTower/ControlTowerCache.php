<?php

namespace App\Services\ControlTower;

use App\Models\ControlTowerDailySnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ControlTowerCache
{
    /**
     * @param callable():array{payload:array,signals:array<int,array<string,mixed>>} $resolver
     * @return array{payload:array,signals:array<int,array<string,mixed>>}
     */
    public function remember(
        int $tenantId,
        CarbonImmutable $date,
        string $view,
        int $rangeDays,
        ?string $marketplace,
        callable $resolver
    ): array {
        $key = $this->cacheKey($tenantId, $date, $view, $rangeDays, $marketplace);
        $ttl = $this->ttlMinutes($date);

        return Cache::remember($key, now()->addMinutes($ttl), $resolver);
    }

    /**
     * @param array<int,array<string,mixed>> $signals
     */
    public function put(
        int $tenantId,
        CarbonImmutable $date,
        string $view,
        int $rangeDays,
        ?string $marketplace,
        array $payload,
        array $signals
    ): void {
        $key = $this->cacheKey($tenantId, $date, $view, $rangeDays, $marketplace);
        Cache::put($key, ['payload' => $payload, 'signals' => $signals], now()->addMinutes($this->ttlMinutes($date)));
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function storeSnapshot(int $tenantId, CarbonImmutable $date, array $payload): void
    {
        if (!Schema::hasTable('control_tower_daily_snapshots')) {
            return;
        }

        ControlTowerDailySnapshot::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'date' => $date->toDateString(),
            ],
            [
                'payload' => $payload,
            ]
        );
    }

    private function cacheKey(int $tenantId, CarbonImmutable $date, string $view, int $rangeDays, ?string $marketplace): string
    {
        $mp = $marketplace && $marketplace !== '' ? strtolower($marketplace) : 'all';
        return sprintf('control_tower:%d:%s:%s:%dd:%s', $tenantId, $date->toDateString(), strtolower($view), $rangeDays, $mp);
    }

    private function ttlMinutes(CarbonImmutable $date): int
    {
        return $date->toDateString() === now()->toDateString() ? 15 : (24 * 60);
    }
}

