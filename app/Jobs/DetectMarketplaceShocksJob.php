<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ActionEngine\PriceHistoryBuilder;
use App\Services\ActionEngine\ShockDetector;
use App\Services\Modules\ModuleGate;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectMarketplaceShocksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(
        public int $userId,
        public string $date,
        public int $windowDays = 45
    ) {
        $this->onQueue('default');
    }

    public function handle(
        ModuleGate $moduleGate,
        PriceHistoryBuilder $priceHistoryBuilder,
        ShockDetector $detector
    ): void {
        $user = User::query()->find($this->userId);
        if (!$user) {
            return;
        }
        if (!$moduleGate->isEnabledForUser($user, 'action_engine')) {
            return;
        }

        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $asOf = CarbonImmutable::parse($this->date);
        $from = $asOf->subDays(max(1, $this->windowDays - 1));

        $priceHistoryBuilder->buildRange($tenantId, (int) $user->id, $from, $asOf);
        $detector->detectForTenant($tenantId, (int) $user->id, $asOf, $this->windowDays);
    }
}

