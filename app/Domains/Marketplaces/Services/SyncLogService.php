<?php

namespace App\Domains\Marketplaces\Services;

use App\Domains\Settlements\Models\SyncLog;

class SyncLogService
{
    public function info(int $syncJobId, string $message, array $context = []): void
    {
        $this->write($syncJobId, 'info', $message, $context);
    }

    public function error(int $syncJobId, string $message, array $context = []): void
    {
        $this->write($syncJobId, 'error', $message, $context);
    }

    private function write(int $syncJobId, string $level, string $message, array $context = []): void
    {
        SyncLog::query()->create([
            'sync_job_id' => $syncJobId,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'created_at' => now(),
        ]);
    }
}

