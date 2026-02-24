<?php

namespace App\Jobs;

use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Support\SettlementExportStateStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateSettlementExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $payoutId,
        public string $token
    ) {
    }

    public function handle(SettlementExportStateStore $store): void
    {
        $payout = Payout::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('id', $this->payoutId)
            ->where('tenant_id', $this->tenantId)
            ->first();

        if (!$payout) {
            $store->putFailed($this->token, $this->tenantId, $this->payoutId, 'Payout not found.');
            return;
        }

        $store->putProcessing($this->token, $this->tenantId, $this->payoutId);

        $directory = "exports/settlements/{$this->tenantId}";
        $filename = "settlement-{$payout->id}-".now()->format('Ymd-His').'.csv';
        $relativePath = "{$directory}/{$filename}";

        try {
            Storage::disk('local')->makeDirectory($directory);
            $absolutePath = Storage::disk('local')->path($relativePath);

            $handle = fopen($absolutePath, 'wb');
            if ($handle === false) {
                throw new \RuntimeException('Failed to open export file for writing.');
            }

            fputcsv($handle, ['type', 'reference_id', 'amount', 'vat_amount']);

            $payout->transactions()
                ->select(['type', 'reference_id', 'amount', 'vat_amount'])
                ->orderBy('id')
                ->chunk(1000, function ($rows) use ($handle): void {
                    foreach ($rows as $tx) {
                        fputcsv($handle, [$tx->type, $tx->reference_id, $tx->amount, $tx->vat_amount]);
                    }
                });

            fclose($handle);

            $store->putReady($this->token, $this->tenantId, $this->payoutId, $relativePath, $filename);
        } catch (Throwable $e) {
            $store->putFailed($this->token, $this->tenantId, $this->payoutId, $e->getMessage());
            throw $e;
        }
    }
}

