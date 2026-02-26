<?php

namespace App\Jobs;

use App\Models\ControlTowerSignal;
use App\Models\User;
use App\Services\ControlTower\ControlTowerAggregator;
use App\Services\ControlTower\ControlTowerCache;
use App\Services\ControlTower\SignalEngine;
use App\Services\Modules\ModuleGate;
use App\Services\Notifications\NotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;

class BuildControlTowerDailySnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;

    public function __construct(
        public int $userId,
        public string $date
    ) {
        $this->onQueue('default');
    }

    public function handle(
        ControlTowerAggregator $aggregator,
        SignalEngine $signalEngine,
        ControlTowerCache $cache,
        ModuleGate $moduleGate,
        NotificationService $notifications
    ): void {
        $user = User::query()->find($this->userId);
        if (!$user || !$moduleGate->isEnabledForUser($user, 'feature.control_tower')) {
            return;
        }

        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $date = CarbonImmutable::parse($this->date);
        $payload = $aggregator->aggregateDaily($tenantId, $date, 30);
        $signals = $signalEngine->generateSignals($tenantId, $date, $payload);

        $cache->storeSnapshot($tenantId, $date, $payload);
        $cache->put($tenantId, $date, 'cfo', 30, null, $payload, $signals);
        $cache->put($tenantId, $date, 'ops', 30, null, $payload, $signals);

        if (Schema::hasTable('control_tower_signals')) {
            foreach ($signals as $signal) {
                ControlTowerSignal::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'date' => $date->toDateString(),
                        'type' => (string) ($signal['type'] ?? ''),
                        'marketplace' => data_get($signal, 'marketplace'),
                        'sku' => data_get($signal, 'sku'),
                    ],
                    [
                        'scope' => (string) ($signal['scope'] ?? 'global'),
                        'severity' => (string) ($signal['severity'] ?? 'info'),
                        'title' => (string) ($signal['title'] ?? 'Signal'),
                        'message' => (string) ($signal['message'] ?? ''),
                        'drivers' => is_array($signal['drivers'] ?? null) ? $signal['drivers'] : null,
                        'action_hint' => is_array($signal['action_hint'] ?? null) ? $signal['action_hint'] : null,
                        'is_resolved' => false,
                        'resolved_at' => null,
                    ]
                );

                if (($signal['severity'] ?? 'info') !== 'critical') {
                    continue;
                }

                $notifications->createNotification([
                    'tenant_id' => $tenantId,
                    'user_id' => $this->resolveOwnerUserId($tenantId),
                    'source' => 'control_tower',
                    'type' => 'critical',
                    'channel' => 'in_app',
                    'title' => (string) ($signal['title'] ?? 'Control Tower Kritik Sinyal'),
                    'body' => (string) ($signal['message'] ?? ''),
                    'data' => [
                        'signal_type' => $signal['type'] ?? null,
                        'marketplace' => $signal['marketplace'] ?? null,
                        'sku' => $signal['sku'] ?? null,
                        'date' => $date->toDateString(),
                    ],
                    'action_url' => '/admin/control-tower?view=ops&date='.$date->toDateString(),
                    'dedupe_key' => sprintf(
                        'control_tower:%d:%s:%s:%s:%s',
                        $tenantId,
                        $date->toDateString(),
                        (string) ($signal['type'] ?? ''),
                        (string) ($signal['marketplace'] ?? 'all'),
                        (string) ($signal['sku'] ?? 'all')
                    ),
                    'dedupe_window_minutes' => 1440,
                ]);
            }
        }
    }

    private function resolveOwnerUserId(int $tenantId): ?int
    {
        $owner = User::query()
            ->where('id', $tenantId)
            ->orWhere(fn ($q) => $q->where('tenant_id', $tenantId)->where('role', 'client'))
            ->orderBy('id')
            ->first();

        return $owner ? (int) $owner->id : null;
    }
}

