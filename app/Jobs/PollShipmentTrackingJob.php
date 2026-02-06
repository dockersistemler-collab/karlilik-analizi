<?php

namespace App\Jobs;

use App\Models\Shipment;
use App\Services\Cargo\ShipmentTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollShipmentTrackingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $shipmentId)
    {
    }

    public function handle(ShipmentTrackingService $service): void
    {
        $shipment = Shipment::query()->find($this->shipmentId);
        if (!$shipment) {
            return;
        }
$service->poll($shipment);
    }
}
