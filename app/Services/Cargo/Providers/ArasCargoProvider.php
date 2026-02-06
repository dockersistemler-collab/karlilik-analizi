<?php

namespace App\Services\Cargo\Providers;

use App\Models\Order;
use Illuminate\Support\Str;

class ArasCargoProvider implements CargoProviderInterface
{
    /**
     * @param array<string,mixed> $credentials
     */
    public function __construct(private readonly array $credentials = [])
    {
    }

    public function createShipment(Order $order): CargoProviderResult
    {
        $tracking = 'ARAS'.Str::upper(Str::random(9));

        return new CargoProviderResult(true, $tracking, 'created', [
            'provider' => 'aras',
            'stub' => true,
        ]);
    }

    public function track(string $trackingNo): CargoProviderResult
    {
        return new CargoProviderResult(true, $trackingNo, 'in_transit', [
            'provider' => 'aras',
            'stub' => true,
        ]);
    }

    public function cancel(string $trackingNo): CargoProviderResult
    {
        return new CargoProviderResult(true, $trackingNo, 'cancelled', [
            'provider' => 'aras',
            'stub' => true,
        ]);
    }
}
