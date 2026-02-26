<?php

namespace App\Observers;

use App\Events\OrderStatusChanged;
use App\Events\QuotaWarningReached;
use App\Jobs\CalculateOrderProfitJob;
use App\Models\Order;
use App\Services\Modules\ModuleGate;

class OrderObserver
{
    public function created(Order $order): void
    {
        $this->maybeAutoCreateShipment($order);
        $this->dispatchProfitCalculation($order);

        $user = $order->user;
        if (!$user || $user->isSuperAdmin()) {
            return;
        }
$subscription = $user->subscription;
        $plan = $subscription?->plan;
$limit = (int) ($plan?->max_orders_per_month ?? 0);
        if ($limit <= 0 || !$subscription || !$subscription->isActive()) {
            return;
        }
$used = Order::query()
            ->where('user_id', $user->id)
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->count();

        if ($used >= (int) ceil($limit * 0.8)) {
            event(new QuotaWarningReached(
                $user->id,
                'orders_per_month',
                $used,
                $limit,
                80,
                now()->format('Y-m'),
                now()->toDateTimeString()
            ));
        }
    }

    public function updated(Order $order): void
    {
        if (!$order->wasChanged('status')) {
            if ($order->wasChanged('cargo_company')) {
                $this->maybeAutoCreateShipment($order);
            }
            return;
        }
$old = (string) $order->getOriginal('status');
        $new = (string) $order->status;
        if ($old === '' || $new === '' || $old === $new) {
            return;
        }

        event(new OrderStatusChanged($order->id, $old, $new));

        if (in_array($new, ['delivered', 'cancelled'], true)) {
            $order->shipment_status = $new;
            $order->save();

            $event = $new === 'delivered' ? 'shipment.delivered' : 'shipment.cancelled';
            app(\App\Services\Cargo\CargoShipmentService::class)->dispatchShipmentEvent($order, $event);
        }

        if ($order->wasChanged('cargo_company')) {
            $this->maybeAutoCreateShipment($order);
        }
    }

    private function maybeAutoCreateShipment(Order $order): void
    {
        if (!is_string($order->cargo_company) || trim($order->cargo_company) === '') {
            return;
        }

        app(\App\Services\Cargo\ShipmentTrackingService::class)->ensureShipmentForOrder($order);

        if (is_string($order->shipment_provider_key) && trim($order->shipment_provider_key) !== '') {
            return;
        }

        app(\App\Services\Cargo\CargoShipmentService::class)->maybeCreateShipmentFromOrder($order);
    }

    private function dispatchProfitCalculation(Order $order): void
    {
        if (!$order->user) {
            return;
        }

        if (!app(ModuleGate::class)->isEnabledForUser($order->user, 'profit_engine')) {
            return;
        }

        CalculateOrderProfitJob::dispatch($order->id);
    }
}
