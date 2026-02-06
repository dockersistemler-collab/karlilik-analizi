<?php

namespace App\Services\Cargo\Providers;

use App\Models\Shipment;
use Illuminate\Support\Carbon;

class FakeCargoProvider implements CargoTrackingProviderInterface
{
    public function fetchTracking(Shipment $shipment): CargoTrackingResult
    {
        $existingCount = $shipment->events()->count();
        $sequence = ['created', 'in_transit', 'delivered'];
        $status = $sequence[min($existingCount, count($sequence) - 1)];

        $event = [
            'event_code' => $status,
            'description' => $this->statusLabel($status),
            'location' => 'ISTANBUL',
            'occurred_at' => Carbon::now()->toDateTimeString(),
            'payload' => [
                'provider' => 'fake',
                'sequence_index' => $existingCount,
            ],
        ];

        return new CargoTrackingResult(true, $status, [$event], ['fake' => true]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'created' => 'Kargo kaydı oluşturuldu',
            'in_transit' => 'Kargo yolda',
            'delivered' => 'Teslim edildi',
            default => 'Durum güncellendi',
        };
    }
}
