<?php

namespace App\Jobs;

use App\Models\MarketplaceKpiSnapshot;
use App\Models\MarketplaceRiskScore;
use App\Models\User;
use App\Services\MarketplaceRisk\NotificationPublisher;
use App\Services\MarketplaceRisk\ProfileResolver;
use App\Services\MarketplaceRisk\RiskCalculator;
use App\Services\Modules\ModuleGate;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateMarketplaceRiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $userId,
        public string $date
    ) {
        $this->onQueue('default');
    }

    public function handle(
        ProfileResolver $profiles,
        RiskCalculator $calculator,
        NotificationPublisher $publisher,
        ModuleGate $moduleGate
    ): void {
        $user = User::query()->find($this->userId);
        if (!$user) {
            return;
        }

        if (!$moduleGate->isEnabledForUser($user, 'marketplace_risk')) {
            return;
        }

        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $date = CarbonImmutable::parse($this->date);

        $snapshots = MarketplaceKpiSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->whereDate('date', $date->toDateString())
            ->get();

        foreach ($snapshots as $snapshot) {
            $marketplace = strtolower((string) $snapshot->marketplace);
            $profile = $profiles->resolveDefault($tenantId, $user->id, $marketplace);

            $result = $calculator->calculate($tenantId, $marketplace, $date, $snapshot, $profile);
            $score = MarketplaceRiskScore::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'marketplace' => $marketplace,
                    'date' => $date->toDateString(),
                ],
                [
                    'user_id' => $user->id,
                    'risk_score' => (float) $result['risk_score'],
                    'status' => (string) $result['status'],
                    'reasons' => $result['reasons'],
                ]
            );

            $publisher->publishIfNeeded(
                $user,
                $marketplace,
                $date,
                (float) $score->risk_score,
                (string) $score->status,
                (array) ($score->reasons ?? [])
            );
        }
    }
}

