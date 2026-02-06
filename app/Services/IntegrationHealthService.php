<?php

namespace App\Services;

use App\Models\Marketplace;
use App\Models\MarketplaceCredential;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IntegrationHealthService
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function getTenantHealthSummary(int $tenantId): array
    {
        $computedAt = Carbon::now();
        $marketplaces = Marketplace::query()
            ->where('is_active', true)
            ->whereHas('credentials', fn ($q) => $q->where('user_id', $tenantId))
            ->with(['credentials' => fn ($q) => $q->where('user_id', $tenantId)])
            ->get();

        if ($marketplaces->isEmpty()) {
            return [];
        }
$codes = $marketplaces->pluck('code')->filter()->values()->all();

        $windowHours = (int) config('integration_health.window_hours', 24);
        $windowStart = $computedAt->copy()->subHours($windowHours);
        $staleMinutes = (int) config('integration_health.stale_minutes', 30);

        $aggregate = $this->fetchNotificationAggregate($tenantId, $codes, $windowStart);
        $lastErrors = $this->fetchLastErrors($tenantId, $codes);
        $stockSuccess = $this->fetchStockSuccess($tenantId, $marketplaces->pluck('id')->all());

        $summary = [];
        foreach ($marketplaces as $marketplace) {
            $code = (string) $marketplace->code;
            $metrics = $this->buildMetrics($code, $aggregate, $lastErrors, $stockSuccess[$marketplace->id] ?? null);

            $status = $this->calculateStatus($metrics['last_success_at'],
                $metrics['last_critical_at'],
                $metrics['last_error_at'],
                $metrics['error_count_24h'],
                $staleMinutes
            );

            $credential = $marketplace->credentials->first();
            $tokenExpiresAt = $credential?->token_expires_at;
$tokenValid = $credential ? (bool) $credential->is_active : false;
            if ($tokenValid && $tokenExpiresAt) {
                $tokenValid = $tokenExpiresAt->greaterThan(Carbon::now());
            }
$summary[] = [
                'marketplace_id' => $marketplace->id,
                'marketplace_code' => $code,
                'marketplace_name' => $marketplace->name,
                'status' => $status,
                'last_success_at' => $metrics['last_success_at'],
                'last_attempt_at' => $metrics['last_attempt_at'],
                'last_error' => $metrics['last_error'],
                'error_count_24h' => $metrics['error_count_24h'],
                'token_expires_at' => $tokenExpiresAt,
                'token_valid' => $tokenValid,
                'actions' => $this->buildActions($marketplace->id, $code),
                'syncs' => $metrics['syncs'],
                'computed_at' => $computedAt,
            ];
        }

        usort($summary, function (array $left, array $right): int {
            $rank = ['DOWN' => 0, 'DEGRADED' => 1, 'OK' => 2];
            $leftRank = $rank[$left['status']] ?? 3;
            $rightRank = $rank[$right['status']] ?? 3;

            if ($leftRank !== $rightRank) {
                return $leftRank <=> $rightRank;
            }

            return strcasecmp((string) ($left['marketplace_name'] ?? ''), (string) ($right['marketplace_name'] ?? ''));
        });

        return $summary;
    }

    /**
     * @return array<string,mixed>
     */
    public function getMarketplaceHealth(int $tenantId, string $marketplace): array
    {
        $summary = $this->getTenantHealthSummary($tenantId);
        foreach ($summary as $item) {
            if ($item['marketplace_code'] === $marketplace) {
                return $item;
            }
        }

        return [];
    }

    /**
     * @param array<string,mixed> $aggregate
     * @param array<string,array<string,string>> $lastErrors
     * @return array<string,mixed>
     */
    private function buildMetrics(string $code, array $aggregate, array $lastErrors, ?Carbon $stockSuccess): array
    {
        $sources = ['order_sync', 'stock_sync', 'invoice'];
        $syncs = [];
        $lastAttempt = null;
        $lastSuccess = $stockSuccess;
        $lastCritical = null;
        $lastErrorAt = null;
        $lastError = null;
        $errorCount = 0;

        foreach ($sources as $source) {
            $key = $code.'.'.$source;
            $row = $aggregate[$key] ?? null;
            $sync = [
                'last_success_at' => $source === 'stock_sync' ? $stockSuccess : null,
                'last_attempt_at' => $row['last_attempt_at'] ?? null,
                'last_error_at' => $row['last_error_at'] ?? null,
                'last_error' => $lastErrors[$key] ?? null,
                'error_count_24h' => (int) ($row['error_count_24h'] ?? 0),
            ];
            $syncs[$source] = $sync;

            $lastAttempt = $this->maxCarbon($lastAttempt, $sync['last_attempt_at']);
            $lastSuccess = $this->maxCarbon($lastSuccess, $sync['last_success_at']);
            $lastCritical = $this->maxCarbon($lastCritical, $row['last_critical_at'] ?? null);
            $lastErrorAt = $this->maxCarbon($lastErrorAt, $row['last_error_at'] ?? null);

            if (!$lastError && $sync['last_error']) {
                $lastError = $sync['last_error'];
            }
$errorCount += (int) $sync['error_count_24h'];
        }

        return [
            'syncs' => $syncs,
            'last_success_at' => $lastSuccess,
            'last_attempt_at' => $lastAttempt,
            'last_error' => $lastError,
            'error_count_24h' => $errorCount,
            'last_critical_at' => $lastCritical,
            'last_error_at' => $lastErrorAt,
        ];
    }

    /**
     * @param array<int,string> $codes
     * @return array<string,mixed>
     */
    private function fetchNotificationAggregate(int $tenantId, array $codes, Carbon $windowStart): array
    {
        $rows = DB::table('app_notifications')
            ->select([
                'marketplace',
                'source',
                DB::raw('MAX(created_at) as last_attempt_at'),
                DB::raw("MAX(CASE WHEN type IN ('critical','operational') THEN created_at END) as last_error_at"),
                DB::raw("MAX(CASE WHEN type = 'critical' THEN created_at END) as last_critical_at"),
                DB::raw("SUM(CASE WHEN type IN ('critical','operational') AND created_at >= '{$windowStart->toDateTimeString()}' THEN 1 ELSE 0 END) as error_count_24h"),
            ])
            ->where('tenant_id', $tenantId)
            ->whereNotNull('marketplace')
            ->whereIn('marketplace', $codes)
            ->whereIn('source', ['order_sync', 'stock_sync', 'invoice'])
            ->groupBy('marketplace', 'source')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $key = $row->marketplace.'.'.$row->source;
            $out[$key] = [
                'last_attempt_at' => $row->last_attempt_at ? Carbon::parse($row->last_attempt_at) : null,
                'last_error_at' => $row->last_error_at ? Carbon::parse($row->last_error_at) : null,
                'last_critical_at' => $row->last_critical_at ? Carbon::parse($row->last_critical_at) : null,
                'error_count_24h' => (int) $row->error_count_24h,
            ];
        }

        return $out;
    }

    /**
     * @param array<int,string> $codes
     * @return array<string,array<string,string>>
     */
    private function fetchLastErrors(int $tenantId, array $codes): array
    {
        $rows = DB::table('app_notifications')
            ->select(['marketplace', 'source', 'title', 'body', 'created_at'])
            ->where('tenant_id', $tenantId)
            ->whereNotNull('marketplace')
            ->whereIn('marketplace', $codes)
            ->whereIn('source', ['order_sync', 'stock_sync', 'invoice'])
            ->whereIn('type', ['critical', 'operational'])
            ->orderBy('created_at', 'desc')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $key = $row->marketplace.'.'.$row->source;
            if (isset($out[$key])) {
                continue;
            }
$title = trim((string) $row->title);
            $body = trim((string) $row->body);
            $message = $title !== '' ? $title : $body;
            if ($title !== '' && $body !== '' && $title !== $body) {
                $message = $title.' - '.$body;
            }
$out[$key] = [
                'message' => $message,
                'at' => $row->created_at,
            ];
        }

        return $out;
    }

    /**
     * @param array<int,int> $marketplaceIds
     * @return array<int,Carbon>
     */
    private function fetchStockSuccess(int $tenantId, array $marketplaceIds): array
    {
        if ($marketplaceIds === []) {
            return [];
        }
$rows = DB::table('marketplace_products')
            ->join('products', 'products.id', '=', 'marketplace_products.product_id')
            ->select('marketplace_products.marketplace_id', DB::raw('MAX(marketplace_products.last_sync_at) as last_sync_at'))
            ->where('products.user_id', $tenantId)
            ->whereIn('marketplace_products.marketplace_id', $marketplaceIds)
            ->groupBy('marketplace_products.marketplace_id')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            if ($row->last_sync_at) {
                $out[(int) $row->marketplace_id] = Carbon::parse($row->last_sync_at);
            }
        }

        return $out;
    }

    private function calculateStatus(?Carbon $lastSuccessAt, ?Carbon $lastCriticalAt, ?Carbon $lastErrorAt, int $errorCount, int $staleMinutes): string
    {
        $staleCutoff = Carbon::now()->subMinutes($staleMinutes);
        $recentSuccess = $lastSuccessAt ? $lastSuccessAt->greaterThanOrEqualTo($staleCutoff) : false;
        $recentCritical = $lastCriticalAt ? $lastCriticalAt->greaterThanOrEqualTo($staleCutoff) : false;
        $recentError = $lastErrorAt ? $lastErrorAt->greaterThanOrEqualTo($staleCutoff) : false;

        $downRequiresCritical = (bool) config('integration_health.down_requires_critical', true);
        if ((($downRequiresCritical && $recentCritical) || (!$downRequiresCritical && $recentError)) && !$recentSuccess) {
            return 'DOWN';
        }
$threshold = (int) config('integration_health.degraded_error_threshold', 1);
        if ($errorCount >= $threshold && $errorCount > 0) {
            return 'DEGRADED';
        }

        return 'OK';
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function buildActions(int $marketplaceId, string $code): array
    {
        return [
            [
                'label' => 'Loglari gor',
                'url' => route('portal.notification-hub.notifications.index', [
                    'marketplace' => $code,
                ]),
            ],
            [
                'label' => 'Entegrasyon ayarlari',
                'url' => route('portal.integrations.edit', [
                    'marketplace' => $marketplaceId,
                ]),
            ],
            [
                'label' => 'Bildirim merkezi',
                'url' => route('portal.notification-hub.notifications.index', [
                    'marketplace' => $code,
                    'read' => 'unread',
                ]),
            ],
        ];
    }

    private function maxCarbon(?Carbon $left, ?Carbon $right): ?Carbon
    {
        if (!$left) {
            return $right;
        }
        if (!$right) {
            return $left;
        }

        return $left->greaterThan($right) ? $left : $right;
    }
}


