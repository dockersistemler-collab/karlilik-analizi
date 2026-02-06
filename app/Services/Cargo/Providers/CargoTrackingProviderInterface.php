<?php

namespace App\Services\Cargo\Providers;

use App\Models\Shipment;

interface CargoTrackingProviderInterface
{
    public function fetchTracking(Shipment $shipment): CargoTrackingResult;
}
