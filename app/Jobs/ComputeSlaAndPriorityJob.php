<?php

namespace App\Jobs;

use App\Models\CommunicationSetting;
use App\Models\CommunicationThread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComputeSlaAndPriorityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $defaultWeights = (array) (CommunicationSetting::query()->whereNull('user_id')->value('priority_weights') ?? []);
        $defaultWeight = (int) ($defaultWeights['time_left'] ?? 3);

        CommunicationThread::query()
            ->with('marketplaceStore')
            ->chunkById(200, function ($threads) use ($defaultWeight): void {
                foreach ($threads as $thread) {
                    if (!$thread->due_at) {
                        continue;
                    }

                    $ownerId = (int) ($thread->marketplaceStore?->user_id ?? 0);
                    $weight = $defaultWeight;
                    if ($ownerId > 0) {
                        $userWeights = (array) (CommunicationSetting::query()
                            ->where('user_id', $ownerId)
                            ->value('priority_weights') ?? []);
                        $weight = (int) ($userWeights['time_left'] ?? $defaultWeight);
                    }

                    $timeLeftMin = now()->diffInMinutes($thread->due_at, false);
                    $overdueBonus = $timeLeftMin < 0 ? 1000 : 0;
                    $urgency = 100 - min(max($timeLeftMin, 0), 100);
                    $priorityScore = 10 + $overdueBonus + ($urgency * $weight);

                    $newStatus = $thread->status;
                    if ($thread->due_at->isPast() && in_array($thread->status, ['open', 'pending'], true)) {
                        $newStatus = 'overdue';
                    }

                    $thread->forceFill([
                        'priority_score' => (int) $priorityScore,
                        'status' => $newStatus,
                    ])->save();
                }
            });
    }
}

