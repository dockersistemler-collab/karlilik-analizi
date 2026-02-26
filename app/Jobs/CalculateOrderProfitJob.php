<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Modules\ModuleGate;
use App\Services\ProfitEngine\ProfitCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateOrderProfitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;

    public function __construct(public int $orderId)
    {
        $this->onQueue('default');
    }

    public function handle(ProfitCalculator $calculator, ModuleGate $moduleGate): void
    {
        $order = Order::query()->with('user')->find($this->orderId);
        if (!$order || !$order->user) {
            return;
        }

        if (!$moduleGate->isEnabledForUser($order->user, 'profit_engine')) {
            return;
        }

        $calculator->calculateAndStore($order);
    }
}

