<?php

namespace App\Jobs;

use App\Models\BuyBoxScore;
use App\Models\MarketplaceOfferSnapshot;
use App\Models\User;
use App\Services\BuyBox\BuyBoxScoreCalculator;
use App\Services\Modules\ModuleGate;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateBuyBoxScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;

    public function __construct(
        public int $tenantId,
        public string $date
    ) {
        $this->onQueue('default');
    }

    public function handle(BuyBoxScoreCalculator $calculator, ModuleGate $moduleGate): void
    {
        $owner = User::query()
            ->where('id', $this->tenantId)
            ->orWhere(function ($q) {
                $q->where('tenant_id', $this->tenantId)->where('role', 'client');
            })
            ->orderBy('id')
            ->first();

        if (!$owner || !$moduleGate->isEnabledForUser($owner, 'buybox_engine')) {
            return;
        }

        $date = Carbon::parse($this->date)->toDateString();
        $snapshots = MarketplaceOfferSnapshot::query()
            ->where('tenant_id', $this->tenantId)
            ->whereDate('date', $date)
            ->get();

        foreach ($snapshots as $snapshot) {
            $result = $calculator->calculate($snapshot);

            $score = BuyBoxScore::query()
                ->where('tenant_id', $snapshot->tenant_id)
                ->where('marketplace', $snapshot->marketplace)
                ->where('sku', $snapshot->sku)
                ->whereDate('date', $date)
                ->first();

            $payload = [
                'tenant_id' => $snapshot->tenant_id,
                'marketplace' => $snapshot->marketplace,
                'date' => $date,
                'sku' => $snapshot->sku,
                'buybox_score' => (int) $result['buybox_score'],
                'status' => (string) $result['status'],
                'win_probability' => (float) $result['win_probability'],
                'drivers' => $result['drivers'],
                'snapshot_id' => $snapshot->id,
            ];

            if ($score) {
                $score->update($payload);
            } else {
                BuyBoxScore::query()->create($payload);
            }
        }
    }
}

