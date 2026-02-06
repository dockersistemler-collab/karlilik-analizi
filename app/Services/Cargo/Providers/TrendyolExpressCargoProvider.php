<?php

namespace App\Services\Cargo\Providers;

use App\Models\Order;
use Illuminate\Support\Str;

class TrendyolExpressCargoProvider implements CargoProviderInterface
{
    /**
     * @param array<string,mixed> $credentials
     */
    public function __construct(private readonly array $credentials = [])
    {
    }

    public function createShipment(Order $order): CargoProviderResult
    {
        $tracking = 'TYX'.Str::upper(Str::random(10));

        return new CargoProviderResult(true, $tracking, 'created', [
            'provider' => 'trendyol_express',
            'stub' => true,
        ]);
    }

    public function track(string $trackingNo): CargoProviderResult
    {
        return new CargoProviderResult(true, $trackingNo, 'in_transit', [
            'provider' => 'trendyol_express',
            'stub' => true,
        ]);
    }

    public function cancel(string $trackingNo): CargoProviderResult
    {
        return new CargoProviderResult(true, $trackingNo, 'cancelled', [
            'provider' => 'trendyol_express',
            'stub' => true,
        ]);
    }
}
