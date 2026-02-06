<?php

namespace App\Services\Cargo;

use App\Models\CargoProviderInstallation;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentTrackingEvent;
use App\Services\Cargo\Providers\CargoTrackingProviderInterface;
use App\Services\Cargo\Providers\CargoTrackingResult;
use App\Services\Cargo\Providers\FakeCargoProvider;
use App\Services\Entitlements\EntitlementService;
use App\Services\Webhooks\WebhookService;
use App\Support\Cargo\CarrierNormalizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ShipmentTrackingService
{
    public function __construct(
        private readonly CargoProviderResolver $resolver,
        private readonly WebhookService $webhooks,
        private readonly EntitlementService $entitlements,
    ) {
    }

    public function ensureShipmentForOrder(Order $order): Shipment
    {
        $order->loadMissing(['user', 'marketplace']);
        $carrierRaw = $this->resolveCarrierName($order);
        $carrierNorm = CarrierNormalizer::normalizeCarrier($carrierRaw);

        $shipment = Shipment::query()->firstOrNew(['order_id' => $order->id]);
        $shipment->user_id = $order->user_id;
        $shipment->marketplace_code = $order->marketplace?->code;
$shipment->carrier_name_raw = $carrierRaw;
        $shipment->carrier_name_normalized = $carrierNorm;
        $shipment->tracking_number = $order->tracking_number;

        $resolved = $this->resolver->resolveForOrder($order);
        if (!$resolved) {
            if (in_array($order->shipment_status, ['unmapped_carrier', 'provider_not_installed'], true)) {
                $shipment->status = $order->shipment_status;
            }
$shipment->save();
            return $shipment;
        }
$shipment->provider_key = $resolved['provider_key'];
        if (is_string($order->shipment_status) && $order->shipment_status !== '') {
            $shipment->status = $order->shipment_status;
        }
$shipment->save();

        return $shipment;
    }

    /**
     * @return array{changed:bool,events:int,status:?string}
     */
    public function poll(Shipment $shipment): array
    {
        $shipment->loadMissing(['order', 'user']);

        if (!$this->shouldPoll($shipment)) {
            return ['changed' => false, 'events' => 0, 'status' => $shipment->status];
        }
$provider = $this->resolveTrackingProvider($shipment);
        if (!$provider) {
            $shipment->status = 'error';
            $shipment->last_error = 'Tracking provider not available.';
            $shipment->last_polled_at = Carbon::now();
            $shipment->save();
            return ['changed' => true, 'events' => 0, 'status' => $shipment->status];
        }
$result = $provider->fetchTracking($shipment);
        if (!$result->success) {
            $shipment->status = 'error';
            $shipment->last_error = $result->error ?: 'Tracking fetch failed.';
            $shipment->last_polled_at = Carbon::now();
            $shipment->save();
            return ['changed' => true, 'events' => 0, 'status' => $shipment->status];
        }
$eventsInserted = $this->persistEvents($shipment, $result);
        $statusChanged = false;

        if ($result->status && $shipment->status !== $result->status) {
            $shipment->status = $result->status;
            $statusChanged = true;
        }
$shipment->last_polled_at = Carbon::now();
        if ($eventsInserted > 0) {
            $lastEventAt = $shipment->events()->max('occurred_at');
            if ($lastEventAt) {
                $shipment->last_event_at = Carbon::parse($lastEventAt);
            }
        }
$shipment->last_error = null;
        $shipment->save();

        $changed = $eventsInserted > 0 || $statusChanged;
        if ($changed) {
            $this->dispatchTrackingEvents($shipment);
        }

        return ['changed' => $changed, 'events' => $eventsInserted, 'status' => $shipment->status];
    }

    public function shouldPoll(Shipment $shipment): bool
    {
        if (in_array($shipment->status, ['delivered', 'returned', 'cancelled'], true)) {
            return false;
        }

        if (!is_string($shipment->tracking_number) || trim($shipment->tracking_number) === '') {
            return false;
        }
$interval = (int) config('cargo.poll_interval_minutes', 15);
        if ($shipment->last_polled_at instanceof Carbon) {
            return $shipment->last_polled_at->diffInMinutes(Carbon::now()) >= $interval;
        }

        return true;
    }

    private function resolveTrackingProvider(Shipment $shipment): ?CargoTrackingProviderInterface
    {
        if (!$shipment->user) {
            return null;
        }
$providerKey = $shipment->provider_key;
        if (!is_string($providerKey) || $providerKey === '') {
            return null;
        }
$installation = CargoProviderInstallation::query()
            ->where('user_id', $shipment->user_id)
            ->where('provider_key', $providerKey)
            ->where('is_active', true)
            ->first();

        if (!$installation) {
            return null;
        }
$meta = is_array($installation->meta) ? $installation->meta : [];
        if (!empty($meta['fake'])) {
            return new FakeCargoProvider();
        }

        return null;
    }

    private function persistEvents(Shipment $shipment, CargoTrackingResult $result): int
    {
        $count = 0;
        foreach ($result->events as $event) {
            if (!is_array($event)) {
                continue;
            }
$hash = sha1(implode('|', [
                $shipment->id,
                $shipment->provider_key,
                (string) ($event['event_code'] ?? ''),
                (string) ($event['description'] ?? ''),
                (string) ($event['location'] ?? ''),
                (string) ($event['occurred_at'] ?? ''),
            ]));

            $exists = ShipmentTrackingEvent::query()
                ->where('shipment_id', $shipment->id)
                ->where('hash', $hash)
                ->exists();
            if ($exists) {
                continue;
            }

            try {
                ShipmentTrackingEvent::query()->create([
                    'shipment_id' => $shipment->id,
                    'provider_key' => $shipment->provider_key,
                    'event_code' => $event['event_code'] ?? null,
                    'description' => $event['description'] ?? null,
                    'location' => $event['location'] ?? null,
                    'occurred_at' => $event['occurred_at'] ?? null,
                    'payload_json' => $event['payload'] ?? null,
                    'hash' => $hash,
                    'created_at' => Carbon::now(),
                ]);
                $count++;
            } catch (\Throwable $e) {
                Log::warning('Shipment tracking event insert failed.', [
                    'shipment_id' => $shipment->id,
                    'hash' => $hash,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    private function dispatchTrackingEvents(Shipment $shipment): void
    {
        if (!$shipment->user || !$this->entitlements->hasModule($shipment->user, 'feature.cargo_webhooks')) {
            return;
        }
$lastEvent = $shipment->events()->orderByDesc('occurred_at')->orderByDesc('id')->first();
        $lastEventHash = $lastEvent?->hash ?? 'none';

        $payload = [
            'shipment' => [
                'id' => $shipment->id,
                'order_id' => $shipment->order_id,
                'status' => $shipment->status,
                'tracking_number' => $shipment->tracking_number,
                'provider_key' => $shipment->provider_key,
            ],
            'order' => [
                'id' => $shipment->order_id,
            ],
            'last_event' => $lastEvent ? [
                'code' => $lastEvent->event_code,
                'description' => $lastEvent->description,
                'occurred_at' => $lastEvent->occurred_at?->toDateTimeString(),
            ] : null,
        ];

        $dedupeSuffix = implode('|', [
            $shipment->id,
            $shipment->status,
            $lastEventHash,
        ]);

        $payload['dedupe_key'] = $dedupeSuffix;

        $this->webhooks->dispatchEvent($shipment->user, 'shipment.tracking_updated', $payload, 'feature.cargo_webhooks');

        if ($shipment->status === 'delivered') {
            $payload['delivered_at'] = $shipment->last_event_at?->toDateTimeString();
$this->webhooks->dispatchEvent($shipment->user, 'shipment.delivered', $payload, 'feature.cargo_webhooks');
        }

        if ($shipment->status === 'returned') {
            $payload['returned_at'] = $shipment->last_event_at?->toDateTimeString();
$this->webhooks->dispatchEvent($shipment->user, 'shipment.returned', $payload, 'feature.cargo_webhooks');
        }
    }

    private function resolveCarrierName(Order $order): ?string
    {
        if (is_string($order->cargo_company) && trim($order->cargo_company) !== '') {
            return trim($order->cargo_company);
        }
$marketplaceData = is_array($order->marketplace_data) ? $order->marketplace_data : [];
        foreach (['cargoCompany', 'shippingProvider', 'cargoProviderName', 'carrier'] as $key) {
            $value = $marketplaceData[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
