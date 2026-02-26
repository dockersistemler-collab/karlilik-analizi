<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ActionEngine\ActionEngine;
use App\Services\Modules\ModuleGate;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunActionEngineDailyJob implements ShouldQueue
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

    public function handle(ActionEngine $engine, ModuleGate $moduleGate): void
    {
        $user = User::query()->find($this->userId);
        if (!$user) {
            return;
        }

        if (!$moduleGate->isEnabledForUser($user, 'action_engine')) {
            return;
        }

        $tenantId = (int) ($user->tenant_id ?: $user->id);
        $engine->runForDate($tenantId, (int) $user->id, CarbonImmutable::parse($this->date));
    }
}

