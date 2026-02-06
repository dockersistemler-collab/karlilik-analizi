<?php

namespace App\Services\Cargo;

use App\Models\Order;
use App\Services\Cargo\Providers\CargoProviderResult;
use App\Services\Entitlements\EntitlementService;
use App\Services\Webhooks\WebhookService;
use Illuminate\Support\Facades\Log;

class CargoShipmentService
{
    public function __construct(
        private readonly CargoProviderResolver $resolver,
        private readonly WebhookService $webhooks,
    ) {
    }

    public function maybeCreateShipmentFromOrder(Order $order): void
    {
        $order->loadMissing(['user', 'marketplace']);

        if (!is_string($order->cargo_company) || trim($order->cargo_company) === '') {
            return;
        }

        if (is_string($order->shipment_provider_key) && $order->shipment_provider_key !== '') {
            return;
        }

        if (!$order->user) {
            return;
        }
$resolved = $this->resolver->resolveForOrder($order);
        if (!$resolved) {
            return;
        }
$provider = $resolved['provider'];
        $providerKey = $resolved['provider_key'];

        try {
            $result = $provider->createShipment($order);
        } catch (\Throwable $e) {
            Log::warning('Cargo shipment create failed.', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'provider_key' => $providerKey,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        if (!$result->success) {
            Log::warning('Cargo shipment create failed (provider response).', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'provider_key' => $providerKey,
                'raw' => $result->raw,
            ]);
            return;
        }
$order->shipment_provider_key = $providerKey;
        $order->shipment_status = $result->status ?: 'created';
        if (is_string($result->trackingNumber) && $result->trackingNumber !== '') {
            $order->tracking_number = $result->trackingNumber;
        }
$order->save();

        $this->dispatchShipmentEvents($order, $result);
    }

    public function dispatchShipmentEvent(Order $order, string $event): void
    {
        $order->loadMissing(['user', 'marketplace']);
        if (!$order->user) {
            return;
        }
$payload = $this->buildPayload($order);
        $this->webhooks->dispatchEvent($order->user, $event, $payload, 'feature.cargo_webhooks');
    }

    private function dispatchShipmentEvents(Order $order, CargoProviderResult $result): void
    {
        if (!$order->user || !app(EntitlementService::class)->hasModule($order->user, 'feature.cargo_webhooks')) {
            return;
        }
$payload = $this->buildPayload($order);

        $this->webhooks->dispatchEvent($order->user, 'shipment.created', $payload, 'feature.cargo_webhooks');

        if (is_string($order->tracking_number) && $order->tracking_number !== '') {
            $this->webhooks->dispatchEvent($order->user, 'shipment.tracking_updated', $payload, 'feature.cargo_webhooks');
        }

        if ($order->shipment_status === 'delivered') {
            $this->webhooks->dispatchEvent($order->user, 'shipment.delivered', $payload, 'feature.cargo_webhooks');
        }

        if ($order->shipment_status === 'cancelled') {
            $this->webhooks->dispatchEvent($order->user, 'shipment.cancelled', $payload, 'feature.cargo_webhooks');
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function buildPayload(Order $order): array
    {
        return [
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'order_number' => $order->order_number,
                'marketplace_order_id' => $order->marketplace_order_id,
                'marketplace' => $order->marketplace?->code ?? $order->marketplace?->name, 'cargo_company' => $order->cargo_company,
                'tracking_number' => $order->tracking_number,
            ],
            'shipment' => [
                'provider_key' => $order->shipment_provider_key,
                'status' => $order->shipment_status,
                'tracking_number' => $order->tracking_number,
            ],
            'user' => [
                'id' => $order->user_id,
            ],
        ];
    }
}
