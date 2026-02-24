<?php

namespace App\Domains\Settlements\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class SettlementDashboardCache
{
    private const TTL_SECONDS = 300;

    public function rememberPortalSummary(int $tenantId, Closure $resolver): array
    {
        return Cache::remember(
            $this->portalSummaryKey($tenantId),
            now()->addSeconds(self::TTL_SECONDS),
            $resolver
        );
    }

    public function rememberApiSummary(int $tenantId, Closure $resolver): array
    {
        return Cache::remember(
            $this->apiSummaryKey($tenantId),
            now()->addSeconds(self::TTL_SECONDS),
            $resolver
        );
    }

    public function forgetAll(int $tenantId): void
    {
        Cache::forget($this->portalSummaryKey($tenantId));
        Cache::forget($this->apiSummaryKey($tenantId));
    }

    private function portalSummaryKey(int $tenantId): string
    {
        return "settlements:dashboard:portal:tenant:{$tenantId}";
    }

    private function apiSummaryKey(int $tenantId): string
    {
        return "settlements:dashboard:api:tenant:{$tenantId}";
    }
}

