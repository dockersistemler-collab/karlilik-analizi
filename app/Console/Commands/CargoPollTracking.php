<?php

namespace App\Console\Commands;

use App\Jobs\PollShipmentTrackingJob;
use App\Models\Shipment;
use App\Services\Cargo\ShipmentTrackingService;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Console\Command;

class CargoPollTracking extends Command
{
    protected $signature = 'cargo:poll-tracking';

    protected $description = 'Poll shipment tracking for active shipments.';

    public function handle(ShipmentTrackingService $service, EntitlementService $entitlements): int
    {
        $max = (int) config('cargo.max_shipments_per_run', 500);

        $query = Shipment::query()
            ->with('user')
            ->whereNotIn('status', ['delivered', 'returned', 'cancelled'])
            ->orderBy('last_polled_at')
            ->limit($max);

        $count = 0;
        $query->chunkById(100, function ($shipments) use ($service, $entitlements, &$count, $max) {
            foreach ($shipments as $shipment) {
                if ($count >= $max) {
                    return false;
                }

                if (!$shipment->user || !$entitlements->hasModule($shipment->user, 'feature.cargo_tracking')) {
                    continue;
                }

                if (!$service->shouldPoll($shipment)) {
                    continue;
                }

                PollShipmentTrackingJob::dispatch($shipment->id);
                $count++;
            }
        });

        $this->info("Queued {$count} shipment(s) for tracking poll.");

        return self::SUCCESS;
    }
}
