<?php

namespace App\Services\Cargo\Providers;

use App\Models\Order;

interface CargoProviderInterface
{
    public function createShipment(Order $order): CargoProviderResult;

    public function track(string $trackingNo): CargoProviderResult;

    public function cancel(string $trackingNo): CargoProviderResult;
}
